<?php

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\Offering;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * A Head of Department and a programme plan whose courses sit in Year 1
 * Semester 1, Year 2 Semester 3 and Year 3 Semester 5, plus one elective
 * course that belongs to no term at all.
 *
 * @return array{0: User, 1: CoursePlan, 2: Course}
 */
function makeHodWithOfferingPlan(): array
{
    $program = Program::create(['code' => 'HOD', 'name' => 'Offering Programme', 'duration_years' => 3]);
    $plan = CoursePlan::create([
        'program_id' => $program->id,
        'code' => 'HOD2025-LOCAL',
        'name' => 'Offering Plan',
    ]);

    $core = CourseType::create(['code' => 'CT02', 'name' => 'Computing Core']);
    $pool = CourseType::create(['code' => CourseType::ELECTIVE_COURSE, 'name' => 'Elective Course']);

    $scheduled = [
        [1, 'HOD 10103', 'First Semester Course'],
        [3, 'HOD 20103', 'Third Semester Course'],
        [5, 'HOD 30103', 'Fifth Semester Course'],
    ];

    foreach ($scheduled as [$semester, $code, $name]) {
        Course::create([
            'code' => $code, 'name' => $name, 'credits' => 3,
            'semester' => $semester, 'course_plan_id' => $plan->id, 'course_type_id' => $core->id,
        ]);
    }

    // Mirrors the live catalogue: electives carry the word "Elective" where a
    // semester would otherwise be, so they have no year to be grouped into.
    $elective = Course::create([
        'code' => 'HOD 3**03', 'name' => 'Elective To Pick', 'credits' => 3,
        'semester' => 'Elective', 'course_plan_id' => $plan->id, 'course_type_id' => $pool->id,
    ]);

    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);

    return [$hod, $plan, $elective];
}

test('the offering grid groups each plan by year and semester', function () {
    [$hod] = makeHodWithOfferingPlan();

    $this->actingAs($hod)
        ->get(route('advisor.offering.grid'))
        ->assertOk()
        ->assertSee('Year 1')
        ->assertSee('Year 2')
        ->assertSee('Year 3')
        ->assertSee('Semester 1')
        ->assertSee('Semester 3')
        ->assertSee('Semester 5')
        ->assertSee('Odd semester')
        ->assertSee('Even semester')
        ->assertSee('HOD 10103')
        ->assertSee('HOD 20103')
        ->assertSee('HOD 30103')
        // The year the course sits in travels with the toggle.
        ->assertSee('name="year" value="1"', false)
        ->assertSee('name="year" value="2"', false)
        ->assertSee('name="year" value="3"', false);
});

test('the offering grid can be narrowed to one course plan', function () {
    [$hod, $plan] = makeHodWithOfferingPlan();

    // A second plan with its own course, not part of the first.
    $program = Program::create(['code' => 'OTH', 'name' => 'Other Programme', 'duration_years' => 3]);
    $other = CoursePlan::create([
        'program_id' => $program->id,
        'code' => 'OTHER2025-LOCAL',
        'name' => 'Other Plan',
    ]);

    $course = Course::create([
        'code' => 'OTH 10103', 'name' => 'Other Plan Course', 'credits' => 3,
        'semester' => 1, 'course_plan_id' => $other->id,
    ]);

    // Both plans are listed so the filter can offer them…
    $this->actingAs($hod)
        ->get(route('advisor.offering.grid'))
        ->assertOk()
        ->assertSee('OTH 10103')
        ->assertSee('HOD 10103')
        ->assertSee($plan->code)
        ->assertSee($other->code);

    // …and choosing one leaves only its courses on the page.
    $response = $this->actingAs($hod)
        ->get(route('advisor.offering.grid', ['plan_id' => $plan->id]))
        ->assertOk()
        ->assertSee('HOD 10103')
        ->assertDontSee('OTH 10103');

    expect($response->getContent())->toContain('value="'.$plan->id.'" selected');

    $this->actingAs($hod)
        ->get(route('advisor.offering.grid', ['plan_id' => $other->id]))
        ->assertOk()
        ->assertSee($course->code)
        ->assertDontSee('HOD 10103');
});

test('elective courses are listed in their own panel and toggle without a year', function () {
    [$hod, $plan, $elective] = makeHodWithOfferingPlan();

    $response = $this->actingAs($hod)
        ->get(route('advisor.offering.grid'))
        ->assertOk()
        ->assertSee('Elective Course')
        ->assertSee($elective->code.' · '.$elective->name);

    // The elective panel is the only place an elective is listed, and its
    // toggle form carries no year — the student picks the term.
    $form = Str::before(Str::after($response->getContent(), 'name="course_id" value="'.$elective->id.'">'), '</form>');

    expect($form)->toContain('course_plan_id')
        ->and($form)->not->toContain('name="year"')
        // An elective has no term of its own, so no semester is shown either.
        ->and($form)->not->toContain('Sem 0');

    $this->actingAs($hod)
        ->post(route('advisor.offering.toggle'), [
            'course_id' => $elective->id,
            'course_plan_id' => $plan->id,
        ])
        ->assertSessionHasNoErrors();

    $offering = Offering::where('course_id', $elective->id)->firstOrFail();

    expect($offering->year)->toBeNull()
        ->and($offering->is_offered)->toBeTrue();
});
