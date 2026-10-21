<?php

use App\Http\Controllers\Admin\LecturerController;
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
        ->and(Hash::check('password', $plain->password))->toBeFalse()
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
        'password' => 'password',
    ])->assertRedirect(route('advisor.dashboard'));

    $this->assertAuthenticatedAs($lecturer);
});

test('re-importing a lecturer without a password leaves the current one alone', function () {
    importLecturers(implode("\n", [
        'lecturer_name,lecturer_email,lecturer_id,department_code,password',
        'Ezah Ahmad,ezak@uthm.edu.my,ezak,3,secret123',
    ])."\n");

    $lecturer = User::where('email', 'ezak@uthm.edu.my')->firstOrFail();

    // Second import: same person, new name, blank password column.
    importLecturers(implode("\n", [
        'lecturer_name,lecturer_email,lecturer_id,department_code,password',
        'Dr. Ezak Fadzrin,ezak@uthm.edu.my,ezak,3,',
    ])."\n");

    $lecturer->refresh();

    expect($lecturer->name)->toBe('Dr. Ezak Fadzrin')
        ->and(Hash::check('secret123', $lecturer->password))->toBeTrue();
});
