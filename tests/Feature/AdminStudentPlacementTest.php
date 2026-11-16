<?php

use App\Models\CoursePlan;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Two programmes (each with a plan), one advisor with two advisees, and one
 * student without an advisor.
 *
 * @return array{0: User, 1: User, 2: Program, 3: CoursePlan, 4: Program, 5: CoursePlan, 6: User, 7: User}
 */
function makeAdvisorPlacementFixture(): array
{
    $department = Department::create(['code' => 'FSKTM', 'name' => 'Faculty of Computing']);

    $cs = Program::create([
        'code' => 'CS', 'name' => 'Computer Science',
        'duration_years' => 4, 'department_id' => $department->id,
    ]);
    $csPlan = CoursePlan::create([
        'program_id' => $cs->id, 'code' => 'CS241', 'name' => 'CS Plan',
    ]);

    $is = Program::create([
        'code' => 'IS', 'name' => 'Information Systems',
        'duration_years' => 4, 'department_id' => $department->id,
    ]);
    $isPlan = CoursePlan::create([
        'program_id' => $is->id, 'code' => 'IS223', 'name' => 'IS Plan',
    ]);

    $advisor = User::factory()->create([
        'role' => 'advisor', 'name' => 'Advisor One', 'staff_id' => 'adv1',
    ]);

    $student = User::factory()->create([
        'role' => 'student', 'name' => 'Advisee One', 'student_id' => 'A100001',
        'email' => 'advisee1@student.fsktm.test',
        'program_id' => $cs->id, 'plan_id' => $csPlan->id,
        'department_id' => $department->id, 'advisor_id' => 'adv1',
    ]);

    $other = User::factory()->create([
        'role' => 'student', 'name' => 'Advisee Two', 'student_id' => 'A100002',
        'email' => 'advisee2@student.fsktm.test',
        'program_id' => $cs->id, 'plan_id' => $csPlan->id,
        'department_id' => $department->id, 'advisor_id' => 'adv1',
    ]);

    $noAdvisor = User::factory()->create([
        'role' => 'student', 'name' => 'Unassigned Student', 'student_id' => 'A100003',
        'email' => 'noadvisor@student.fsktm.test', 'advisor_id' => null,
    ]);

    return [$advisor, $student, $cs, $csPlan, $is, $isPlan, $other, $noAdvisor];
}

test('an administrator can browse students by PAK advisor', function () {
    [$advisor, $student, , , , , $other, $noAdvisor] = makeAdvisorPlacementFixture();

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.students.by-advisor'))
        ->assertOk()
        ->assertSee('Students by PAK Advisor')
        ->assertSee($advisor->name)
        ->assertSee('adv1')
        ->assertSee('2 student(s)')
        ->assertSee('1 student(s) without a PAK advisor');

    $this->actingAs($admin)
        ->get(route('admin.students.by-advisor', ['advisor' => $advisor->id]))
        ->assertOk()
        ->assertSee($student->name)
        ->assertSee($other->name)
        ->assertDontSee($noAdvisor->name);
});

test('the unassigned group lists students without an advisor', function () {
    [, , , , , , , $noAdvisor] = makeAdvisorPlacementFixture();

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.students.by-advisor', ['advisor' => 'none']))
        ->assertOk()
        ->assertSee('Students without a PAK advisor')
        ->assertSee($noAdvisor->name);
});

test('an administrator can change the programme and plan from the advisor view', function () {
    [$advisor, $student, , , $is, $isPlan] = makeAdvisorPlacementFixture();

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put(route('admin.students.placement', $student), [
            'program_id' => $is->id,
            'plan_id' => $isPlan->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $student->refresh();

    expect($student->program_id)->toBe($is->id)
        ->and($student->plan_id)->toBe($isPlan->id)
        ->and($student->department_id)->toBe($is->department_id);

    // Redirecting back keeps the advisor page in view.
    $this->actingAs($admin)
        ->get(route('admin.students.by-advisor', ['advisor' => $advisor->id]))
        ->assertOk()
        ->assertSee($is->code);
});

test('a plan from another programme is rejected', function () {
    [$advisor, $student, , $csPlan, $is, $isPlan] = makeAdvisorPlacementFixture();

    $admin = User::factory()->create(['role' => 'admin']);
    $original = [$student->program_id, $student->plan_id, $student->department_id];

    $this->actingAs($admin)
        ->put(route('admin.students.placement', $student), [
            'program_id' => $is->id,
            'plan_id' => $csPlan->id,
        ])
        ->assertSessionHasErrors('plan_id');

    $student->refresh();

    expect([$student->program_id, $student->plan_id, $student->department_id])->toBe($original);
});

test('choosing only a plan adopts its programme', function () {
    [, $student, , , $is, $isPlan] = makeAdvisorPlacementFixture();

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put(route('admin.students.placement', $student), [
            'program_id' => null,
            'plan_id' => $isPlan->id,
        ])
        ->assertSessionHasNoErrors();

    $student->refresh();

    expect($student->program_id)->toBe($is->id)
        ->and($student->plan_id)->toBe($isPlan->id);
});

test('a student account can only be re-placed by an administrator', function () {
    [$advisor, $student, , , $is, $isPlan] = makeAdvisorPlacementFixture();

    $this->actingAs($advisor)
        ->get(route('admin.students.by-advisor'))
        ->assertForbidden();

    $this->actingAs($advisor)
        ->put(route('admin.students.placement', $student), [
            'program_id' => $is->id,
            'plan_id' => $isPlan->id,
        ])
        ->assertForbidden();

    expect($student->fresh()->program_id)->not->toBe($is->id);
});

test('changing the programme without a plan drops the old programme plan', function () {
    [, $student, , , $is] = makeAdvisorPlacementFixture();

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put(route('admin.students.placement', $student), [
            'program_id' => $is->id,
            'plan_id' => null,
        ])
        ->assertSessionHasNoErrors();

    $student->refresh();

    expect($student->program_id)->toBe($is->id)
        ->and($student->plan_id)->toBeNull()
        ->and($student->department_id)->toBe($is->department_id);
});
