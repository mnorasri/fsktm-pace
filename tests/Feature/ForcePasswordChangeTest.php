<?php

use App\Http\Controllers\Admin\LecturerController;
use App\Http\Controllers\Admin\StudentController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * A student account that still carries the faculty default password.
 */
function defaultPasswordStudent(): User
{
    return User::factory()->create([
        'role' => 'student',
        'student_id' => 'A220001',
        'email' => 'default@student.fsktm.test',
        'password' => StudentController::DEFAULT_PASSWORD,
        'must_change_password' => true,
    ]);
}

test('signing in with the default password asks for a new one first', function () {
    $student = defaultPasswordStudent();

    $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => StudentController::DEFAULT_PASSWORD,
    ])->assertRedirect(route('password.change'));

    $this->assertAuthenticatedAs($student);
});

test('a flagged student cannot reach the student portal', function () {
    $student = defaultPasswordStudent();

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertRedirect(route('password.change'));

    $this->actingAs($student)
        ->get(route('profile'))
        ->assertRedirect(route('password.change'));
});

test('the change password screen explains why it is shown', function () {
    $student = defaultPasswordStudent();

    $this->actingAs($student)
        ->get(route('password.change'))
        ->assertOk()
        ->assertSee('Choose a new password')
        ->assertSee($student->email);
});

test('changing the default password clears the flag and opens the dashboard', function () {
    $student = defaultPasswordStudent();

    $this->actingAs($student)
        ->put(route('password.change.update'), [
            'current_password' => StudentController::DEFAULT_PASSWORD,
            'password' => 'my-own-secret',
            'password_confirmation' => 'my-own-secret',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('student.dashboard'));

    $student->refresh();

    expect($student->must_change_password)->toBeFalse()
        ->and(Hash::check('my-own-secret', $student->password))->toBeTrue();

    // The portal now opens normally.
    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk();
});

test('the new password must differ from the default one', function () {
    $student = defaultPasswordStudent();

    $this->actingAs($student)
        ->put(route('password.change.update'), [
            'current_password' => StudentController::DEFAULT_PASSWORD,
            'password' => StudentController::DEFAULT_PASSWORD,
            'password_confirmation' => StudentController::DEFAULT_PASSWORD,
        ])
        ->assertSessionHasErrors('password');

    expect($student->fresh()->must_change_password)->toBeTrue();
});

test('a student with their own password is not forced to change it', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'email' => 'own@student.fsktm.test',
        'password' => 'my-own-secret',
        'must_change_password' => false,
    ]);

    $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'my-own-secret',
    ])->assertRedirect(route('student.dashboard'));

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk();
});

test('an administrator password reset forces a change at the next sign-in', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create([
        'role' => 'student',
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.students.reset-password', $student))
        ->assertSessionHasNoErrors();

    expect($student->fresh()->must_change_password)->toBeTrue();
});

test('students imported with the default password must change it', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $csv = "matric_num,student_name,student_email,program_code,plan_code,advisor_id,password\n"
        ."A220010,Default Import,import-default@student.fsktm.test,,,,\n"
        ."A220011,Custom Import,import-custom@student.fsktm.test,,,,own-secret\n";

    $this->actingAs($admin)
        ->post(route('admin.students.import'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])
        ->assertSessionHasNoErrors();

    expect(User::where('student_id', 'A220010')->firstOrFail()->must_change_password)->toBeTrue()
        ->and(User::where('student_id', 'A220011')->firstOrFail()->must_change_password)->toBeFalse();
});

test('a lecturer created with the default password must change it', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.lecturers.store'), [
            'name' => 'Dr. Default Lecturer',
            'email' => 'default-lecturer@fsktm.test',
            'staff_id' => 'xdefault',
        ])
        ->assertSessionHasNoErrors();

    $lecturer = User::where('staff_id', 'xdefault')->firstOrFail();

    expect($lecturer->must_change_password)->toBeTrue()
        ->and(Hash::check(LecturerController::DEFAULT_PASSWORD, $lecturer->password))->toBeTrue();

    auth()->logout();

    $this->post(route('login.store'), [
        'email' => $lecturer->email,
        'password' => LecturerController::DEFAULT_PASSWORD,
    ])->assertRedirect(route('password.change'));

    $this->assertAuthenticatedAs($lecturer);
});

test('a lecturer who sets their own password is not forced to change it', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.lecturers.store'), [
            'name' => 'Dr. Own Password',
            'email' => 'own-lecturer@fsktm.test',
            'staff_id' => 'xowner',
            'password' => 'lecturer-secret',
        ])
        ->assertSessionHasNoErrors();

    $lecturer = User::where('staff_id', 'xowner')->firstOrFail();

    expect($lecturer->must_change_password)->toBeFalse();

    auth()->logout();

    $this->post(route('login.store'), [
        'email' => $lecturer->email,
        'password' => 'lecturer-secret',
    ])->assertRedirect(route('advisor.dashboard'));
});

test('an administrator password reset forces a lecturer to change it', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $lecturer = User::factory()->create([
        'role' => 'advisor',
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.lecturers.reset-password', $lecturer))
        ->assertSessionHasNoErrors();

    expect($lecturer->fresh()->must_change_password)->toBeTrue();
});

test('lecturers imported with the default password must change it', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $csv = "staff_id,lecturer_name,lecturer_email,department_code,password\n"
        ."ximportdef,Default Lecturer,import-default@fsktm.test,,\n"
        ."ximportown,Own Lecturer,import-own@fsktm.test,,own-secret\n";

    $this->actingAs($admin)
        ->post(route('admin.lecturers.import'), [
            'file' => UploadedFile::fake()->createWithContent('lecturers.csv', $csv),
        ])
        ->assertSessionHasNoErrors();

    expect(User::where('staff_id', 'ximportdef')->firstOrFail()->must_change_password)->toBeTrue()
        ->and(User::where('staff_id', 'ximportown')->firstOrFail()->must_change_password)->toBeFalse();
});
