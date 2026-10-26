<?php

use App\Http\Controllers\Admin\LecturerController;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * Import a lecturer CSV as the administrator would.
 */
function importLecturers(string $csv): void
{
    $admin = User::factory()->create(['role' => 'admin']);

    test()->actingAs($admin)->post(route('admin.lecturers.import'), [
        'file' => UploadedFile::fake()->createWithContent('lecturers.csv', $csv),
    ])->assertSessionHasNoErrors();

    // Drop the acting-as administrator so the test can sign in on its own.
    auth()->logout();
}

test('the lecturer import stores plain passwords once and hashes untouched', function () {
    $existingHash = Hash::make('old-system-password');

    importLecturers(implode("\n", [
        'lecturer_name,lecturer_email,lecturer_id,department_code,password',
        'Fakri Othman,fakri@uthm.edu.my,fakri,,secret123',
        'Hanifah Murli,hanifah@uthm.edu.my,hanifah,,',
        'Farhan Fudzee,farhan@uthm.edu.my,farhan,,'.$existingHash,
    ])."\n");

    $plain = User::where('email', 'fakri@uthm.edu.my')->firstOrFail();

    // A plain password is hashed exactly once.
    expect(Hash::check('secret123', $plain->password))->toBeTrue()
        ->and(Hash::check(LecturerController::DEFAULT_PASSWORD, $plain->password))->toBeFalse()
        ->and($plain->password)->not->toBe('secret123');

    // A blank cell falls back to the documented default.
    $blank = User::where('email', 'hanifah@uthm.edu.my')->firstOrFail();
    expect(Hash::check(LecturerController::DEFAULT_PASSWORD, $blank->password))->toBeTrue();

    // An already-hashed value is kept verbatim instead of being hashed again
    // (which would lock the account out).
    $hashed = User::where('email', 'farhan@uthm.edu.my')->firstOrFail();
    expect($hashed->password)->toBe($existingHash)
        ->and(Hash::check('old-system-password', $hashed->password))->toBeTrue();
});

test('imported lecturers can sign in with the password the import stored', function () {
    importLecturers(implode("\n", [
        'lecturer_name,lecturer_email,lecturer_id,department_code,password',
        'Fakri Othman,fakri@uthm.edu.my,fakri,,secret123',
    ])."\n");

    $lecturer = User::where('email', 'fakri@uthm.edu.my')->firstOrFail();

    expect($lecturer->role)->toBe(User::ROLE_ADVISOR)
        ->and($lecturer->status)->toBe('active');

    $this->post(route('login.store'), [
        'email' => 'fakri@uthm.edu.my',
        'password' => 'secret123',
    ])->assertRedirect(route('advisor.dashboard'));

    $this->assertAuthenticatedAs($lecturer);

    // The advisor portal opens for them.
    $this->get(route('advisor.dashboard'))->assertOk();
});

test('a lecturer created without a password can sign in with the default', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.lecturers.store'), [
        'name' => 'Suriati Suparjoh',
        'email' => 'suriati@uthm.edu.my',
        'staff_id' => 'suriati',
    ])->assertSessionHasNoErrors();

    $lecturer = User::where('email', 'suriati@uthm.edu.my')->firstOrFail();

    expect(Hash::check(LecturerController::DEFAULT_PASSWORD, $lecturer->password))->toBeTrue();

    // Sign out the administrator first: the login page is guest-only.
    auth()->logout();

    $this->post(route('login.store'), [
        'email' => 'suriati@uthm.edu.my',
        'password' => LecturerController::DEFAULT_PASSWORD,
    ])->assertRedirect(route('advisor.dashboard'));

    $this->assertAuthenticatedAs($lecturer);
});

test('re-importing a lecturer without a password leaves the current one alone', function () {
    Department::create(['code' => 'JKP', 'name' => 'Software Engineering']);

    importLecturers(implode("\n", [
        'lecturer_name,lecturer_email,lecturer_id,department_code,password',
        'Ezah Ahmad,ezak@uthm.edu.my,ezak,JKP,secret123',
    ])."\n");

    $lecturer = User::where('email', 'ezak@uthm.edu.my')->firstOrFail();

    // Second import: same person, new name, blank password column.
    importLecturers(implode("\n", [
        'lecturer_name,lecturer_email,lecturer_id,department_code,password',
        'Dr. Ezak Fadzrin,ezak@uthm.edu.my,ezak,JKP,',
    ])."\n");

    $lecturer->refresh();

    expect($lecturer->name)->toBe('Dr. Ezak Fadzrin')
        ->and(Hash::check('secret123', $lecturer->password))->toBeTrue();
});

test('a new lecturer uses staff id, name, email, department and password in that order', function () {
    Department::create(['code' => 'JKP', 'name' => 'Software Engineering']);

    // The columns follow the documented structure.
    importLecturers(implode("\n", [
        'staff_id,lecturer_name,lecturer_email,department_code,password',
        'LEC100,Nur Aisyah,nur@uthm.edu.my,JKP,secret123',
    ])."\n");

    $lecturer = User::where('staff_id', 'LEC100')->firstOrFail();

    expect($lecturer->name)->toBe('Nur Aisyah')
        ->and($lecturer->email)->toBe('nur@uthm.edu.my')
        ->and($lecturer->department?->code)->toBe('JKP')
        ->and(Hash::check('secret123', $lecturer->password))->toBeTrue();
});

test('the staff id keys the import even when the email column changes', function () {
    importLecturers("staff_id,lecturer_name,lecturer_email,department_code,password\nLEC200,First Name,first@uthm.edu.my,,\n");
    importLecturers("staff_id,lecturer_name,lecturer_email,department_code,password\nLEC200,Renamed Lecturer,second@uthm.edu.my,,\n");

    // One lecturer, matched by the staff ID rather than creating a duplicate.
    expect(User::where('staff_id', 'LEC200')->count())->toBe(1);

    $lecturer = User::where('staff_id', 'LEC200')->firstOrFail();

    expect($lecturer->name)->toBe('Renamed Lecturer')
        ->and($lecturer->email)->toBe('second@uthm.edu.my');
});

test('a new lecturer cannot be created without a staff id', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.lecturers.store'), [
        'name' => 'No Staff Id',
        'email' => 'nostaff@uthm.edu.my',
    ])->assertSessionHasErrors('staff_id');

    expect(User::where('email', 'nostaff@uthm.edu.my')->exists())->toBeFalse();
});

test('renaming a staff id carries the advisees along', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $advisor = User::factory()->create(['role' => 'advisor', 'staff_id' => 'oldstaff']);

    $student = User::factory()->create([
        'role' => 'student',
        'student_id' => 'A210020',
        'advisor_id' => 'oldstaff',
    ]);

    $this->actingAs($admin)->put(route('admin.lecturers.update', $advisor), [
        'name' => $advisor->name,
        'email' => $advisor->email,
        'staff_id' => 'newstaff',
    ])->assertSessionHasNoErrors();

    expect($advisor->fresh()->staff_id)->toBe('newstaff')
        ->and($student->fresh()->advisor_id)->toBe('newstaff');
});

test('removing a lecturer clears the advisor link of their advisees', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $advisor = User::factory()->create(['role' => 'advisor', 'staff_id' => 'removed']);

    $student = User::factory()->create([
        'role' => 'student',
        'student_id' => 'A210021',
        'advisor_id' => 'removed',
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.lecturers.destroy', $advisor))
        ->assertSessionHasNoErrors();

    expect(User::find($advisor->id))->toBeNull()
        ->and($student->fresh()->advisor_id)->toBeNull();
});
