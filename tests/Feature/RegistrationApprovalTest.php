<?php

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Offering;
use App\Models\PreRegistration;
use App\Models\Program;
use App\Models\RegistrationSubmission;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A student with an approved plan, their advisor, and two offered courses in
 * the active term (20262027 · Semester 2).
 *
 * @return array{0: User, 1: User, 2: Course, 3: Course}
 */
function makeBatchStudent(): array
{
    $program = Program::create(['code' => 'BAT', 'name' => 'Batch Programme']);
    $plan = CoursePlan::create([
        'program_id' => $program->id,
        'code' => 'BAT2025-LOCAL',
        'name' => 'Batch Plan',
    ]);

    $first = Course::create([
        'code' => 'BAT 10203', 'name' => 'First Even Course', 'credits' => 3,
        'semester' => 2, 'course_plan_id' => $plan->id,
    ]);
    $second = Course::create([
        'code' => 'BAT 10403', 'name' => 'Second Even Course', 'credits' => 3,
        'semester' => 2, 'course_plan_id' => $plan->id,
    ]);

    $advisor = User::factory()->create(['role' => 'advisor']);

    $student = User::factory()->create([
        'role' => 'student',
        'program_id' => $program->id,
        'plan_id' => $plan->id,
        'advisor_id' => $advisor->id,
        'plan_status' => 'approved',
    ]);

    Setting::put('active_session', '20262027');
    Setting::put('active_semester', '2');

    return [$student, $advisor, $first, $second];
}

function offerBatchCourse(Course $course): Offering
{
    return Offering::create([
        'course_id' => $course->id,
        'session' => '20262027',
        'semester' => '2',
        'section' => '01',
        'capacity' => 40,
        'is_offered' => true,
    ]);
}

test('a submitted pre-registration is locked until the advisor responds', function () {
    [$student, $advisor, $first, $second] = makeBatchStudent();

    $offeringA = offerBatchCourse($first);
    $offeringB = offerBatchCourse($second);

    $this->actingAs($student)->post(route('student.pre-registration.store', $offeringA))->assertSessionHasNoErrors();

    // Before submitting, the batch sits with the student and can be sent.
    $html = $this->actingAs($student)->get(route('student.pre-registration'))->assertOk()->getContent();

    expect($html)->toContain('Send your pre-registration to your advisor for approval')
        ->and($html)->toContain('Not submitted yet')
        ->and($html)->toContain(route('student.pre-registration.submit'));

    $this->actingAs($student)->post(route('student.pre-registration.submit'))->assertSessionHasNoErrors();

    expect(RegistrationSubmission::where('user_id', $student->id)->value('status'))->toBe('submitted');

    // The page now says it is with the advisor and offers no way to change it.
    $html = $this->actingAs($student)->get(route('student.pre-registration'))->assertOk()->getContent();
    $registration = PreRegistration::where('user_id', $student->id)->first();

    expect($html)->toContain('awaiting your advisor&rsquo;s approval')
        ->and($html)->toContain('Awaiting advisor approval')
        ->and($html)->not->toContain(route('student.pre-registration.destroy', $registration));

    // Server-side the term is locked: no new courses, no withdrawals.
    $this->actingAs($student)
        ->post(route('student.pre-registration.store', $offeringB))
        ->assertSessionHasErrors('offering');

    $this->actingAs($student)
        ->delete(route('student.pre-registration.destroy', $registration))
        ->assertForbidden();

    expect(PreRegistration::where('user_id', $student->id)->count())->toBe(1)
        ->and(PreRegistration::where('user_id', $student->id)->where('status', 'pending')->count())->toBe(1);
});

test('the advisor approves the whole pre-registration and every course is confirmed', function () {
    [$student, $advisor, $first, $second] = makeBatchStudent();

    $offeringA = offerBatchCourse($first);
    $offeringB = offerBatchCourse($second);

    $this->actingAs($student)->post(route('student.pre-registration.store', $offeringA))->assertSessionHasNoErrors();
    $this->actingAs($student)->post(route('student.pre-registration.store', $offeringB))->assertSessionHasNoErrors();
    $this->actingAs($student)->post(route('student.pre-registration.submit'))->assertSessionHasNoErrors();

    $this->actingAs($advisor)
        ->post(route('advisor.registrations.batch.approve', $student))
        ->assertSessionHasNoErrors();

    $submission = RegistrationSubmission::where('user_id', $student->id)->first();

    expect($submission->status)->toBe('approved')
        ->and($submission->reviewed_by)->toBe($advisor->id)
        ->and(PreRegistration::where('user_id', $student->id)->where('status', 'approved')->count())->toBe(2);

    $this->actingAs($student)
        ->get(route('student.pre-registration'))
        ->assertOk()
        ->assertSee('Pre-registration approved by '.$advisor->name)
        ->assertSee('Approved by advisor');

    // The printable slip carries the advisor's decision too.
    $this->actingAs($student)
        ->get(route('student.slip'))
        ->assertOk()
        ->assertSee('Approved by '.$advisor->name);

    // An approved registration stays locked.
    $this->actingAs($student)
        ->post(route('student.pre-registration.store', $offeringA))
        ->assertSessionHasErrors('offering');
});

test('returning the batch unlocks it, keeps the note, and allows a resubmission', function () {
    [$student, $advisor, $first, $second] = makeBatchStudent();

    $offeringA = offerBatchCourse($first);
    $offeringB = offerBatchCourse($second);

    $this->actingAs($student)->post(route('student.pre-registration.store', $offeringA))->assertSessionHasNoErrors();
    $this->actingAs($student)->post(route('student.pre-registration.submit'))->assertSessionHasNoErrors();

    $this->actingAs($advisor)
        ->post(route('advisor.registrations.batch.return', $student), ['note' => 'Please add the lab course.'])
        ->assertSessionHasNoErrors();

    expect(RegistrationSubmission::where('user_id', $student->id)->value('status'))->toBe('returned');

    $this->actingAs($student)
        ->get(route('student.pre-registration'))
        ->assertOk()
        ->assertSee('Returned by your advisor')
        ->assertSee('Please add the lab course.');

    // The student may edit again …
    $this->actingAs($student)->post(route('student.pre-registration.store', $offeringB))->assertSessionHasNoErrors();
    $this->actingAs($student)
        ->delete(route('student.pre-registration.destroy', PreRegistration::where('offering_id', $offeringB->id)->first()))
        ->assertSessionHasNoErrors();

    // … and resubmit, which clears the previous note.
    $this->actingAs($student)->post(route('student.pre-registration.submit'))->assertSessionHasNoErrors();

    $submission = RegistrationSubmission::where('user_id', $student->id)->first();

    expect($submission->status)->toBe('submitted')
        ->and($submission->note)->toBeNull();
});

test('the batch can only be sent with an approved plan and at least one course', function () {
    [$student, $advisor, $first, $second] = makeBatchStudent();

    $offeringA = offerBatchCourse($first);

    // Nothing selected yet.
    $this->actingAs($student)
        ->post(route('student.pre-registration.submit'))
        ->assertSessionHasErrors('registration');

    expect(RegistrationSubmission::where('user_id', $student->id)->exists())->toBeFalse();

    // A plan that has not been approved blocks the submission outright.
    $student->update(['plan_status' => 'submitted']);
    $this->actingAs($student)->post(route('student.pre-registration.store', $offeringA))->assertSessionHasErrors('offering');

    $student->update(['plan_status' => 'approved']);
    $this->actingAs($student)->post(route('student.pre-registration.store', $offeringA))->assertSessionHasNoErrors();

    $student->update(['plan_status' => 'draft']);
    $this->actingAs($student)
        ->post(route('student.pre-registration.submit'))
        ->assertSessionHasErrors('registration');

    expect(RegistrationSubmission::where('user_id', $student->id)->exists())->toBeFalse();
});

test('an unrelated advisor cannot decide the batch', function () {
    [$student, $advisor, $first, $second] = makeBatchStudent();

    $this->actingAs($student)->post(route('student.pre-registration.store', offerBatchCourse($first)))->assertSessionHasNoErrors();
    $this->actingAs($student)->post(route('student.pre-registration.submit'))->assertSessionHasNoErrors();

    $stranger = User::factory()->create(['role' => 'advisor']);

    $this->actingAs($stranger)
        ->post(route('advisor.registrations.batch.approve', $student))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->post(route('advisor.registrations.batch.return', $student))
        ->assertForbidden();

    // A student with nothing submitted cannot be decided either.
    $other = User::factory()->create([
        'role' => 'student',
        'advisor_id' => $advisor->id,
        'program_id' => $student->program_id,
        'plan_id' => $student->plan_id,
    ]);

    $this->actingAs($advisor)
        ->post(route('advisor.registrations.batch.approve', $other))
        ->assertSessionHasErrors('registration');

    expect(RegistrationSubmission::where('user_id', $student->id)->value('status'))->toBe('submitted');
});

test('the advisor dashboard shows the batch awaiting their decision', function () {
    [$student, $advisor, $first, $second] = makeBatchStudent();

    $this->actingAs($student)->post(route('student.pre-registration.store', offerBatchCourse($first)))->assertSessionHasNoErrors();

    $registration = PreRegistration::where('user_id', $student->id)->first();

    // Not submitted yet → the panel says so, shows the slip-style table, and
    // still offers the per-course decision.
    $this->actingAs($advisor)
        ->get(route('advisor.dashboard'))
        ->assertOk()
        ->assertSee('Not submitted yet')
        ->assertSee('Registrations to Approve')
        ->assertSee('Course Title')
        ->assertSee('Total Credits')
        ->assertSee('Maximum load per term:')
        ->assertSee($first->code)
        ->assertSee(route('advisor.registrations.approve', $registration), false)
        ->assertSee(route('advisor.registrations.reject', $registration), false)
        ->assertDontSee('Approve registration');

    $this->actingAs($student)->post(route('student.pre-registration.submit'))->assertSessionHasNoErrors();

    $this->actingAs($advisor)
        ->get(route('advisor.dashboard'))
        ->assertOk()
        ->assertSee('Awaiting your approval')
        ->assertSee('Approve registration')
        ->assertSee('Approve the whole registration')
        ->assertSee(route('advisor.registrations.batch.approve', $student), false)
        ->assertSee(route('advisor.registrations.batch.return', $student), false)
        // The per-course buttons step aside for the batch decision.
        ->assertDontSee(route('advisor.registrations.approve', $registration), false)
        ->assertDontSee(route('advisor.registrations.reject', $registration), false);
});
