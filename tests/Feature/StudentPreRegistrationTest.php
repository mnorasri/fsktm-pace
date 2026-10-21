<?php

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Offering;
use App\Models\PreRegistration;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Build a student with three plan courses: one odd, one even without
 * prerequisites and one even with an unmet prerequisite.
 *
 * @return array{0: User, 1: CoursePlan, 2: Course, 3: Course, 4: Course}
 */
function makePreRegStudent(): array
{
    $program = Program::create(['code' => 'PRE', 'name' => 'Pre-registration Programme']);
    $plan = CoursePlan::create([
        'program_id' => $program->id,
        'code' => 'PRE2025-LOCAL',
        'name' => 'Pre-registration Plan',
    ]);

    $odd = Course::create([
        'code' => 'PRE 10103', 'name' => 'Odd Course', 'credits' => 3,
        'semester' => 1, 'course_plan_id' => $plan->id,
    ]);
    $even = Course::create([
        'code' => 'PRE 10203', 'name' => 'Even Course', 'credits' => 3,
        'semester' => 2, 'course_plan_id' => $plan->id,
    ]);
    $locked = Course::create([
        'code' => 'PRE 20303', 'name' => 'Locked Course', 'credits' => 3,
        'semester' => 2, 'course_plan_id' => $plan->id, 'prerequisites' => 'PRE 10103',
    ]);

    $student = User::factory()->create([
        'role' => 'student',
        'program_id' => $program->id,
        'plan_id' => $plan->id,
        // Registration only opens for an approved plan; individual tests that
        // care about the gate set their own status.
        'plan_status' => 'approved',
    ]);

    return [$student, $plan, $odd, $even, $locked];
}

function offerInActiveTerm(Course $course, bool $offered = true): Offering
{
    return Offering::create([
        'course_id' => $course->id,
        'session' => '20262027',
        'semester' => '2',
        'section' => '01',
        'capacity' => 40,
        'is_offered' => $offered,
    ]);
}

test('pre-registration mirrors the study plan and only enables offered courses', function () {
    [$student, $plan, $odd, $even, $locked] = makePreRegStudent();

    Setting::put('active_session', '20262027');
    Setting::put('active_semester', '2');

    // Only the odd course and the locked one are published for the term.
    offerInActiveTerm($odd);
    offerInActiveTerm($locked);

    $response = $this->actingAs($student)->get(route('student.pre-registration'))->assertOk();
    $html = $response->getContent();

    // The saved plan supplies the list, laid out in the odd / even grid.
    $response->assertSee($odd->code)
        ->assertSee($even->code)
        ->assertSee($locked->code)
        ->assertSee('Semester 1')
        ->assertSee('Semester 2');

    expect(substr_count($html, 'btn-primary btn-sm'))->toBe(1)
        ->and($html)->toContain('Locked — complete prerequisite(s): '.$odd->code)
        ->and(substr_count($html, '>Not offered<'))->toBe(1)
        // Only the offered, prerequisite-clear course carries an Add form.
        ->and(substr_count($html, route('student.pre-registration.store', Offering::where('course_id', $odd->id)->first())))->toBe(1)
        ->and(substr_count($html, route('student.pre-registration.store', Offering::where('course_id', $locked->id)->first())))->toBe(0);
});

test('an offered course can be added while a course that is not offered is refused', function () {
    [$student, $plan, $odd, $even] = makePreRegStudent();

    Setting::put('active_session', '20262027');
    Setting::put('active_semester', '2');

    $offered = offerInActiveTerm($odd);
    $hidden = offerInActiveTerm($even, offered: false);

    // Published → the student may pre-register.
    $this->actingAs($student)
        ->post(route('student.pre-registration.store', $offered))
        ->assertSessionHasNoErrors();

    expect(PreRegistration::where('user_id', $student->id)->where('offering_id', $offered->id)->exists())->toBeTrue();

    // Withdrawn → refused, even though the offering row exists.
    $this->actingAs($student)
        ->post(route('student.pre-registration.store', $hidden))
        ->assertSessionHasErrors('offering');

    expect(PreRegistration::where('offering_id', $hidden->id)->exists())->toBeFalse();

    // The page shows the added course as Selected; the withdrawn course and the
    // one that was never published stay disabled.
    $response = $this->actingAs($student)->get(route('student.pre-registration'))->assertOk();

    expect($response->getContent())->toContain('Selected')
        ->and(substr_count($response->getContent(), '>Not offered<'))->toBe(2)
        ->and(substr_count($response->getContent(), 'btn-primary btn-sm'))->toBe(0);
});

test('registration stays locked until the advisor approves the study plan', function () {
    [$student, $plan, $odd, $even] = makePreRegStudent();

    Setting::put('active_session', '20262027');
    Setting::put('active_semester', '2');

    $offered = offerInActiveTerm($odd);

    $student->update(['plan_status' => 'submitted']);

    $response = $this->actingAs($student)->get(route('student.pre-registration'))->assertOk();

    // The plan is still listed — only the registration itself is on hold.
    $response->assertSee('Registration is locked until your advisor approves your study plan')
        ->assertSee($odd->code);

    expect(substr_count($response->getContent(), 'btn-primary btn-sm'))->toBe(0)
        ->and($response->getContent())->toContain('Awaiting approval of your study plan.');

    // The server refuses a crafted request too.
    $this->actingAs($student)
        ->post(route('student.pre-registration.store', $offered))
        ->assertSessionHasErrors('offering');

    expect(PreRegistration::where('user_id', $student->id)->count())->toBe(0);

    // Approval opens the same course up.
    $student->update(['plan_status' => 'approved']);

    $html = $this->actingAs($student)->get(route('student.pre-registration'))->assertOk()->getContent();

    expect(substr_count($html, 'btn-primary btn-sm'))->toBe(1);
});

test('an offered course is disabled once the credit cap is reached', function () {
    [$student, $plan, $odd, $even] = makePreRegStudent();

    Setting::put('active_session', '20262027');
    Setting::put('active_semester', '2');

    $first = offerInActiveTerm($odd);
    offerInActiveTerm($even);

    // 19 of the 20 credits are taken, so the 3-credit course no longer fits.
    PreRegistration::create([
        'user_id' => $student->id,
        'offering_id' => $first->id,
        'session' => '20262027',
        'semester' => '2',
        'credits' => 19,
        'status' => 'pending',
    ]);

    $html = $this->actingAs($student)->get(route('student.pre-registration'))->assertOk()->getContent();

    expect($html)->toContain('Credit cap reached — remove a course before adding '.$even->code)
        ->and(substr_count($html, 'btn-primary btn-sm'))->toBe(0);
});
