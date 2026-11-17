<?php

use App\Http\Controllers\Admin\StudentController;
use App\Models\CoursePlan;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('a head of department gets the student directory with HoD links and actions', function () {
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($hod)->get(route('advisor.students.index'))
        ->assertOk()
        ->assertSee('Manage Students')
        ->assertSee($student->name)
        ->assertSee(route('advisor.students.index'), false)
        ->assertSee(route('advisor.students.by-advisor'), false)
        ->assertSee(route('advisor.students.store'), false)
        ->assertSee(route('advisor.students.import'), false)
        ->assertSee(route('advisor.students.update', $student), false)
        ->assertSee(route('advisor.students.destroy', $student), false)
        ->assertSee(route('advisor.students.reset-password', $student), false)
        ->assertDontSee(route('admin.students.index'), false);
});

test('a head of department can browse students by PAK advisor', function () {
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);
    $advisor = User::factory()->create(['role' => 'advisor', 'staff_id' => 'pak-one']);
    $student = User::factory()->create(['role' => 'student', 'advisor_id' => $advisor->staff_id]);

    $this->actingAs($hod)->get(route('advisor.students.by-advisor'))
        ->assertOk()
        ->assertSee($advisor->name);

    $this->get(route('advisor.students.by-advisor', ['advisor' => $advisor->id]))
        ->assertOk()
        ->assertSee($student->name)
        ->assertSee(route('advisor.students.placement', $student), false)
        ->assertSee(route('advisor.students.index'), false);
});

test('a head of department can create and edit student accounts', function () {
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);
    $attributes = [
        'name' => 'New Student',
        'email' => 'new@student.test',
        'student_id' => 'HOD001',
        'advisor_id' => null,
    ];

    $this->actingAs($hod)->post(route('advisor.students.store'), $attributes)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', $attributes + ['role' => 'student', 'must_change_password' => true]);
    $student = User::where('student_id', 'HOD001')->firstOrFail();
    expect(Hash::check(StudentController::DEFAULT_PASSWORD, $student->password))->toBeTrue();

    $this->put(route('advisor.students.update', $student), [
        ...$attributes,
        'name' => 'Updated Student',
        'status' => 'inactive',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', [
        'id' => $student->id,
        'name' => 'Updated Student',
        'status' => 'inactive',
        'role' => 'student',
    ]);
});

test('a head of department can assign a programme and study plan', function () {
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);
    $student = User::factory()->create(['role' => 'student']);
    $department = Department::create(['code' => 'HOD', 'name' => 'Computing']);
    $program = Program::create([
        'code' => 'CS', 'name' => 'Computer Science', 'department_id' => $department->id,
    ]);
    $plan = CoursePlan::create(['code' => 'CS2026', 'name' => 'CS Plan', 'program_id' => $program->id]);

    $this->actingAs($hod)->put(route('advisor.students.placement', $student), [
        'program_id' => $program->id,
        'plan_id' => $plan->id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', [
        'id' => $student->id,
        'program_id' => $program->id,
        'plan_id' => $plan->id,
        'department_id' => $department->id,
    ]);
});

test('a head of department can import students from CSV', function () {
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);

    $this->actingAs($hod)->post(route('advisor.students.import'), [
        'file' => UploadedFile::fake()->createWithContent(
            'students.csv',
            "matric_num,student_name,student_email\nHOD002,Imported Student,imported@student.test\n"
        ),
    ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('import_report.created', 1);

    $this->assertDatabaseHas('users', [
        'student_id' => 'HOD002',
        'name' => 'Imported Student',
        'email' => 'imported@student.test',
        'role' => 'student',
        'must_change_password' => true,
    ]);
});

test('a head of department can reset a student password', function () {
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);
    $student = User::factory()->create(['role' => 'student']);
    $oldPassword = $student->password;

    $this->actingAs($hod)->post(route('advisor.students.reset-password', $student))
        ->assertRedirect()
        ->assertSessionHas('reset_password.name', $student->name);

    $student->refresh();
    expect($student->password)->not->toBe($oldPassword);
    expect(Hash::check(session('reset_password.password'), $student->password))->toBeTrue();
    expect($student->must_change_password)->toBeTrue();
});

test('a head of department can delete a student account', function () {
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($hod)->delete(route('advisor.students.destroy', $student))->assertRedirect();

    $this->assertModelMissing($student);
});

test('HoD student creation applies the existing required field validation', function () {
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);

    $this->actingAs($hod)->post(route('advisor.students.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'student_id']);

    $this->assertDatabaseCount('users', 1);
});

test('HoD student actions cannot target staff accounts', function (string $method, string $action) {
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);
    $staff = User::factory()->create(['role' => 'advisor']);
    $originalPassword = $staff->password;

    $this->actingAs($hod)->{$method}(route('advisor.students.'.$action, $staff))->assertNotFound();

    $this->assertModelExists($staff);
    expect($staff->fresh()->password)->toBe($originalPassword);
})->with([
    ['put', 'placement'],
    ['put', 'update'],
    ['delete', 'destroy'],
    ['post', 'reset-password'],
]);

test('ordinary advisors and students cannot use HoD student management', function (string $method, string $action) {
    $student = User::factory()->create(['role' => 'student']);
    $advisor = User::factory()->create(['role' => 'advisor', 'is_hod' => false]);

    foreach ([$advisor, $student] as $user) {
        $this->actingAs($user)->{$method}(route('advisor.students.'.$action, $student))
            ->assertForbidden();
    }

    $this->assertModelExists($student);
    $this->assertDatabaseCount('users', 2);
})->with([
    ['get', 'index'],
    ['get', 'by-advisor'],
    ['put', 'placement'],
    ['post', 'store'],
    ['post', 'import'],
    ['put', 'update'],
    ['delete', 'destroy'],
    ['post', 'reset-password'],
]);

test('guests must sign in before opening HoD student management', function () {
    $this->get(route('advisor.students.index'))->assertRedirect(route('login'));
});

test('admin student management keeps admin links', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($admin)->get(route('admin.students.index'))
        ->assertOk()
        ->assertSee(route('admin.students.by-advisor'), false)
        ->assertSee(route('admin.students.update', $student), false);
});
