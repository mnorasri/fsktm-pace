<?php

use App\Models\Course;
use App\Models\Offering;
use App\Models\OfferingGroup;
use App\Models\OfferingSection;
use App\Models\PreRegistration;
use App\Models\Program;
use App\Models\RegistrationSubmission;
use App\Models\StudyPlanEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('staff and students use their business identifiers while administrators keep numeric IDs', function () {
    $admin = User::factory()->create(['role' => 'admin', 'id' => '42']);
    $staff = User::factory()->create(['role' => 'advisor', 'staff_id' => 'staff.leading001']);
    $student = User::factory()->create(['role' => 'student', 'student_id' => '001234']);

    expect($admin->refresh()->id)->toBe('42');
    expect($staff->refresh()->id)->toBe('staff.leading001');
    expect($student->refresh()->id)->toBe('001234');
});

test('a student identifier cannot collide with a staff or administrator identifier', function (string $id) {
    $admin = User::factory()->create(['role' => 'admin', 'id' => '42']);
    User::factory()->create(['role' => 'advisor', 'staff_id' => 'shared']);

    $this->actingAs($admin)->post(route('admin.students.store'), [
        'name' => 'Collision', 'email' => 'collision@example.test', 'student_id' => $id,
    ])->assertSessionHasErrors('student_id');

    $this->assertDatabaseMissing('users', ['email' => 'collision@example.test']);
})->with(['shared', '42']);

test('renaming staff carries every assignment and review reference to the new primary key', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $staff = User::factory()->create(['role' => 'advisor', 'staff_id' => 'old.staff']);
    $student = User::factory()->create([
        'role' => 'student', 'advisor_id' => $staff->id, 'plan_reviewed_by' => $staff->id,
    ]);
    $program = Program::create(['code' => 'KEY', 'name' => 'Key Programme', 'head_of_program_id' => $staff->id]);
    $course = Course::create(['code' => 'KEY101', 'name' => 'Key Course', 'credits' => 3]);
    $offering = Offering::create([
        'course_id' => $course->id, 'session' => '2026/2027', 'semester' => '1',
        'lecturer_id' => $staff->id, 'coordinator_id' => $staff->id, 'advisee_group_id' => $staff->id,
    ]);
    $group = OfferingGroup::create([
        'offering_id' => $offering->id, 'advisor_id' => $staff->id, 'lecturer_id' => $staff->id,
    ]);
    $section = OfferingSection::create(['offering_id' => $offering->id, 'section' => '01', 'lecturer_id' => $staff->id]);
    $registration = PreRegistration::create([
        'user_id' => $student->id, 'offering_id' => $offering->id,
        'session' => '2026/2027', 'semester' => '1', 'reviewed_by' => $staff->id,
    ]);
    $submission = RegistrationSubmission::create([
        'user_id' => $student->id, 'session' => '2026/2027', 'semester' => '1', 'reviewed_by' => $staff->id,
    ]);

    $this->actingAs($admin)->put(route('admin.lecturers.update', $staff), [
        'staff_id' => 'new.staff', 'name' => $staff->name, 'email' => $staff->email,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('users', ['id' => 'old.staff']);
    $this->assertDatabaseHas('users', ['id' => 'new.staff', 'staff_id' => 'new.staff', 'remember_token' => null]);
    foreach ([
        [$student, ['advisor_id', 'plan_reviewed_by']],
        [$program, ['head_of_program_id']],
        [$offering, ['lecturer_id', 'coordinator_id', 'advisee_group_id']],
        [$group, ['advisor_id', 'lecturer_id']],
        [$section, ['lecturer_id']],
        [$registration, ['reviewed_by']],
        [$submission, ['reviewed_by']],
    ] as [$model, $columns]) {
        $model->refresh();
        foreach ($columns as $column) {
            expect($model->getAttribute($column))->toBe('new.staff');
        }
    }
});

test('renaming a matric number preserves the student plan and registrations', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'student', 'student_id' => 'OLD001']);
    $course = Course::create(['code' => 'KEY101', 'name' => 'Key Course', 'credits' => 3]);
    $offering = Offering::create(['course_id' => $course->id, 'session' => '2026/2027', 'semester' => '1']);
    $entry = StudyPlanEntry::create(['user_id' => $student->id, 'course_id' => $course->id, 'year' => 1, 'semester' => 1]);
    $registration = PreRegistration::create([
        'user_id' => $student->id, 'offering_id' => $offering->id, 'session' => '2026/2027', 'semester' => '1',
    ]);
    $submission = RegistrationSubmission::create(['user_id' => $student->id, 'session' => '2026/2027', 'semester' => '1']);
    DB::table('sessions')->insert([
        'id' => 'old-session', 'user_id' => $student->id, 'payload' => '', 'last_activity' => time(),
    ]);

    $this->actingAs($admin)->put(route('admin.students.update', $student), [
        'student_id' => 'NEW001', 'name' => $student->name, 'email' => $student->email,
    ])->assertRedirect()->assertSessionHasNoErrors();

    foreach ([$entry, $registration, $submission] as $model) {
        expect($model->refresh()->user_id)->toBe('NEW001');
    }
    $this->assertDatabaseMissing('users', ['id' => 'OLD001']);
    $this->assertDatabaseHas('users', ['id' => 'NEW001', 'student_id' => 'NEW001']);
    $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
});

test('string identifiers work for login and supervisee detail requests', function () {
    $advisor = User::factory()->create(['role' => 'advisor', 'staff_id' => 'pak.alpha']);
    $student = User::factory()->create(['role' => 'student', 'student_id' => 'AI001', 'advisor_id' => $advisor->id]);

    $this->post(route('login.store'), [
        'email' => $advisor->email, 'password' => 'password',
    ])->assertRedirect(route('advisor.dashboard'));

    $this->assertAuthenticatedAs($advisor);
    $this->get(route('advisor.dashboard.detail', ['student' => 'AI001']))
        ->assertOk()->assertViewHas('selected', fn ($row) => $row['student']->id === $student->id);
    $this->get(route('advisor.dashboard', ['student' => 'AI001']))
        ->assertOk()->assertViewHas('selected', fn ($row) => $row['student']->id === 'AI001');
});
