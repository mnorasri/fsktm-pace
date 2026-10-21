<?php

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Program;
use App\Models\StudyPlanEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * An advisor with one advisee whose plan holds four courses: an odd and an
 * even course in Year 1, a Year 2 course, and a Year 1 course that wrongly
 * lists the Year 2 course as its prerequisite.
 *
 * @return array{0: User, 1: User}
 */
function makeAdvisorWithAdvisee(): array
{
    $program = Program::create(['code' => 'ADV', 'name' => 'Advisor Programme', 'duration_years' => 2]);
    $plan = CoursePlan::create([
        'program_id' => $program->id,
        'code' => 'ADV2025-LOCAL',
        'name' => 'Advisor Plan',
    ]);

    $odd = Course::create([
        'code' => 'ADV 10103', 'name' => 'Odd Course', 'credits' => 3,
        'semester' => 1, 'course_plan_id' => $plan->id,
    ]);
    $even = Course::create([
        'code' => 'ADV 10203', 'name' => 'Even Course', 'credits' => 3,
        'semester' => 2, 'course_plan_id' => $plan->id,
    ]);
    $later = Course::create([
        'code' => 'ADV 30103', 'name' => 'Planned Later', 'credits' => 3,
        'semester' => 3, 'course_plan_id' => $plan->id,
    ]);
    $needsLater = Course::create([
        'code' => 'ADV 20203', 'name' => 'Needs A Later Course', 'credits' => 3,
        'semester' => 2, 'course_plan_id' => $plan->id, 'prerequisites' => 'ADV 30103',
    ]);

    $advisor = User::factory()->create(['role' => 'advisor']);

    $student = User::factory()->create([
        'role' => 'student',
        'program_id' => $program->id,
        'plan_id' => $plan->id,
        'advisor_id' => $advisor->id,
        'plan_status' => 'submitted',
    ]);

    // Year 1 Semester 1 → the odd course. Year 1 Semester 2 → both even ones.
    // Year 2 Semester 1 → the course the Year 1 course depends on.
    $slots = [
        [$odd, 1, 1],
        [$even, 1, 2],
        [$needsLater, 1, 2],
        [$later, 2, 1],
    ];

    foreach ($slots as [$course, $year, $semester]) {
        StudyPlanEntry::create([
            'user_id' => $student->id,
            'course_plan_id' => $plan->id,
            'course_id' => $course->id,
            'year' => $year,
            'semester' => $semester,
            'status' => 'PLANNED',
        ]);
    }

    return [$advisor, $student];
}

test('the advisor dashboard shows a supervisee plan as a per-year odd / even grid', function () {
    [$advisor, $student] = makeAdvisorWithAdvisee();

    $response = $this->actingAs($advisor)->get(route('advisor.dashboard'))->assertOk();

    $response->assertSee($student->name)
        ->assertSee('Year 1')
        ->assertSee('Year 2')
        ->assertSee('Semester 1')
        ->assertSee('Semester 2')
        ->assertSee('ADV 10103')
        ->assertSee('ADV 10203')
        ->assertSee('ADV 20203')
        ->assertSee('ADV 30103')
        // The advisor sees the same prerequisite flags the student does.
        ->assertSee('Prereq misplaced: ADV 30103')
        ->assertSee('1 prerequisite violation(s)')
        // And can still approve the plan from the same panel.
        ->assertSee(route('advisor.plans.approve', $student), false);

    $html = $response->getContent();

    // A term card per semester per year, odd before even, and one row per
    // course — Year 2 Semester 2 has none.
    expect(substr_count($html, 'Semester 1</p>'))->toBe(2)
        ->and(substr_count($html, 'Semester 2</p>'))->toBe(2)
        ->and(substr_count($html, 'rounded-xl border border-slate-200/70 bg-slate-50/60 p-3'))->toBe(4)
        ->and($html)->toContain('ADV 10103 · Odd Course')
        ->and($html)->toContain('ADV 20203 · Needs A Later Course')
        ->and($html)->toContain('No courses in this term');
});

test('a supervisee who has not built a plan falls back to the empty state', function () {
    [$advisor, $student] = makeAdvisorWithAdvisee();

    $other = User::factory()->create([
        'role' => 'student',
        'program_id' => $student->program_id,
        'plan_id' => $student->plan_id,
        'advisor_id' => $advisor->id,
    ]);

    $response = $this->actingAs($advisor)->get(route('advisor.dashboard'))->assertOk();

    // The supervisee with a plan gets the grid, the other one the placeholder.
    $response->assertSee($other->name)
        ->assertSee('This student has not built a study plan yet.')
        ->assertSee('2 course(s) · 6 cr')
        ->assertSee('1 course(s) · 3 cr');
});
