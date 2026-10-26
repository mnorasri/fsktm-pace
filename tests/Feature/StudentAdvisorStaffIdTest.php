<?php

use App\Http\Controllers\Admin\LecturerController;
use App\Http\Controllers\Admin\StudentController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('a student links to their advisor through the advisor staff id', function () {
    $advisor = User::factory()->create(['role' => 'advisor', 'staff_id' => 'norasri']);

    $student = User::factory()->create(['role' => 'student', 'advisor_id' => 'norasri']);

    expect($student->advisor?->is($advisor))->toBeTrue()
        ->and($advisor->advisees()->count())->toBe(1)
        ->and($advisor->advisees()->first()->is($student))->toBeTrue();
});

test('a numeric advisor reference no longer resolves to an advisor', function () {
    $advisor = User::factory()->create(['role' => 'advisor', 'staff_id' => 'norasri']);

    // Legacy rows stored the advisor's user id; they must not match an advisor.
    $student = User::factory()->create([
        'role' => 'student',
        'advisor_id' => (string) $advisor->id,
    ]);

    expect($student->advisor)->toBeNull()
        ->and($advisor->advisees()->count())->toBe(0);
});

test('the student import stores the advisor staff id and the password', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    User::factory()->create(['role' => 'advisor', 'staff_id' => 'norasri', 'email' => 'norasri@uthm.edu.my']);
    User::factory()->create(['role' => 'advisor', 'staff_id' => 'nurezayana', 'email' => 'nurezayana@uthm.edu.my']);

    // The first two rows name their advisor with the staff id; the third uses
    // the advisor email, which stays supported as a fallback.
    $csv = implode("\n", [
        'matric_num,student_name,student_email,program_code,plan_code,advisor_id,password',
        'A210001,Ahmad bin Ismail,ahmad@student.fsktm.test,,,norasri,secret123',
        'A210002,Siti Nurhaliza,siti@student.fsktm.test,,,nurezayana,',
        'A210003,Lim Wei Jie,lim@student.fsktm.test,,,norasri@uthm.edu.my,',
    ])."\n";

    $this->actingAs($admin)->post(route('admin.students.import'), [
        'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
    ])->assertSessionHasNoErrors();

    expect(User::where('student_id', 'A210001')->firstOrFail()->advisor_id)->toBe('norasri')
        ->and(User::where('student_id', 'A210002')->firstOrFail()->advisor_id)->toBe('nurezayana')
        ->and(User::where('student_id', 'A210003')->firstOrFail()->advisor_id)->toBe('norasri')
        // The supplied password is hashed; a blank cell keeps the default.
        ->and(Hash::check('secret123', User::where('student_id', 'A210001')->firstOrFail()->password))->toBeTrue()
        ->and(Hash::check(StudentController::DEFAULT_PASSWORD, User::where('student_id', 'A210002')->firstOrFail()->password))->toBeTrue();
});

test('the student import matches by matric number when the email changes', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $import = function (string $csv) use ($admin) {
        $this->actingAs($admin)->post(route('admin.students.import'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])->assertSessionHasNoErrors();
    };

    $import("matric_num,student_name,student_email,program_code,plan_code,advisor_id,password\nA210010,First Name,first@student.fsktm.test,,,,\n");
    $import("matric_num,student_name,student_email,program_code,plan_code,advisor_id,password\nA210010,Renamed Student,second@student.fsktm.test,,,,\n");

    expect(User::where('student_id', 'A210010')->count())->toBe(1);

    $student = User::where('student_id', 'A210010')->firstOrFail();

    expect($student->name)->toBe('Renamed Student')
        ->and($student->email)->toBe('second@student.fsktm.test');
});

test('a student cannot be created without a matric number', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.students.store'), [
        'name' => 'No Matric',
        'email' => 'nomatric@student.fsktm.test',
    ])->assertSessionHasErrors('student_id');

    expect(User::where('email', 'nomatric@student.fsktm.test')->exists())->toBeFalse();
});

test('the admin student form saves the advisor staff id and rejects unknown ones', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $advisor = User::factory()->create(['role' => 'advisor', 'staff_id' => 'norasri']);

    $this->actingAs($admin)->post(route('admin.students.store'), [
        'name' => 'Ahmad bin Ismail',
        'email' => 'ahmad@student.fsktm.test',
        'student_id' => 'A210001',
        'advisor_id' => 'norasri',
    ])->assertSessionHasNoErrors();

    $student = User::where('student_id', 'A210001')->firstOrFail();

    expect($student->advisor_id)->toBe('norasri')
        ->and($student->advisor?->is($advisor))->toBeTrue();

    // Moving the student to no advisor clears the link.
    $this->actingAs($admin)->put(route('admin.students.update', $student), [
        'name' => 'Ahmad bin Ismail',
        'email' => 'ahmad@student.fsktm.test',
        'student_id' => 'A210001',
        'advisor_id' => '',
    ])->assertSessionHasNoErrors();

    expect($student->fresh()->advisor_id)->toBeNull();

    $this->actingAs($admin)->put(route('admin.students.update', $student), [
        'name' => 'Ahmad bin Ismail',
        'email' => 'ahmad@student.fsktm.test',
        'student_id' => 'A210001',
        'advisor_id' => 'ghost-lecturer',
    ])->assertSessionHasErrors('advisor_id');
});

test('a student created without a password signs in with the default', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.students.store'), [
        'student_id' => 'A210030',
        'name' => 'Default Password',
        'email' => 'defaultpw@student.fsktm.test',
    ])->assertSessionHasNoErrors();

    $student = User::where('student_id', 'A210030')->firstOrFail();

    expect(Hash::check(StudentController::DEFAULT_PASSWORD, $student->password))->toBeTrue();

    // Sign out the administrator first: the login page is guest-only.
    auth()->logout();

    $this->post(route('login.store'), [
        'email' => 'defaultpw@student.fsktm.test',
        'password' => StudentController::DEFAULT_PASSWORD,
    ])->assertRedirect(route('student.dashboard'));

    $this->assertAuthenticatedAs($student);
});

test('students and lecturers share the same default password', function () {
    expect(StudentController::DEFAULT_PASSWORD)->toBe('R@hsi4')
        ->and(LecturerController::DEFAULT_PASSWORD)->toBe('R@hsi4');
});
