<?php

use App\Models\Course;
use App\Models\CourseOfferingRequest;
use App\Models\CoursePlan;
use App\Models\Offering;
use App\Models\PlanCourse;
use App\Models\PreRegistration;
use App\Models\Program;
use App\Models\RegistrationSubmission;
use App\Models\Setting;
use App\Models\StudyPlanEntry;
use App\Models\User;
use App\Support\RegistrationSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array{0: User, 1: User, 2: User, 3: Course, 4: CoursePlan} */
function courseRequestFixture(): array
{
    Setting::put('active_session', '2026/2027');
    Setting::put('active_semester', '1');
    $program = Program::create(['code' => 'CRQ', 'name' => 'Course Request Programme']);
    $plan = CoursePlan::create(['program_id' => $program->id, 'code' => 'CRQ2026', 'name' => 'Request Plan']);
    $advisor = User::factory()->create(['role' => 'advisor']);
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);
    $student = User::factory()->create([
        'role' => 'student', 'advisor_id' => $advisor->id, 'program_id' => $program->id, 'plan_id' => $plan->id,
    ]);
    $course = Course::create([
        'code' => 'CRQ 10103', 'name' => 'Requested Course', 'credits' => 3,
        'course_plan_id' => $plan->id, 'semester' => 1,
    ]);

    return [$student, $advisor, $hod, $course, $plan];
}

function pendingCourseRequest(User $student, Course $course, bool $forwarded = false): CourseOfferingRequest
{
    return CourseOfferingRequest::factory()->create([
        'user_id' => $student->id, 'course_id' => $course->id,
        'status' => $forwarded ? CourseOfferingRequest::PENDING_HOD : CourseOfferingRequest::PENDING_ADVISOR,
        'advisor_reviewed_by' => $forwarded ? $student->advisor_id : null,
        'advisor_reviewed_at' => $forwarded ? now() : null,
        'forwarded_at' => $forwarded ? now() : null,
    ]);
}

test('HoD approval registers the student and updates Manage Offering', function () {
    [$student, $advisor, $hod, $course] = courseRequestFixture();

    $this->actingAs($student)->get(route('student.course-requests.index'))
        ->assertOk()->assertSee($course->code);
    $this->post(route('student.course-requests.store'), [
        'course_id' => $course->id, 'reason' => 'Needed to graduate on time.',
        'user_id' => $hod->id, 'status' => 'approved', 'session' => 'wrong',
    ])->assertRedirect(route('student.course-requests.index'))->assertSessionHasNoErrors();

    $record = CourseOfferingRequest::firstOrFail();
    expect($record->user_id)->toBe($student->id);
    expect($record->status)->toBe(CourseOfferingRequest::PENDING_ADVISOR);
    expect($record->session)->toBe('2026/2027');
    $this->assertDatabaseCount('offerings', 0);
    $this->actingAs($hod)->get(route('advisor.course-requests.hod.index'))
        ->assertOk()->assertViewHas('requests', fn ($requests) => $requests->isEmpty());

    $this->actingAs($advisor)->get(route('advisor.course-requests.index'))
        ->assertOk()->assertSee('Needed to graduate on time.');
    $this->post(route('advisor.course-requests.review', $record), [
        'decision' => 'forward', 'note' => 'Recommended for graduation.',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('course_offering_requests', [
        'id' => $record->id, 'status' => 'pending_hod', 'advisor_reviewed_by' => $advisor->id,
    ]);
    $this->assertDatabaseCount('offerings', 0);
    $this->actingAs($hod)->get(route('advisor.course-requests.hod.index'))
        ->assertOk()->assertSee('Recommended for graduation.');
    $this->post(route('advisor.course-requests.hod.review', $record), [
        'decision' => 'approve', 'note' => 'Additional offering approved.',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('course_offering_requests', [
        'id' => $record->id, 'status' => 'approved', 'hod_reviewed_by' => $hod->id,
    ]);
    $this->assertDatabaseHas('offerings', [
        'course_id' => $course->id, 'session' => '2026/2027', 'semester' => '1', 'is_offered' => true,
    ]);
    $this->assertDatabaseHas('pre_registrations', [
        'user_id' => $student->id, 'status' => 'approved', 'reviewed_by' => $hod->id,
        'session' => '2026/2027', 'semester' => '1', 'credits' => 3,
    ]);
    $offering = Offering::where('course_id', $course->id)->firstOrFail();
    $this->actingAs($hod)->get(route('advisor.offering.manage'))
        ->assertOk()
        ->assertViewHas('offerings', fn ($offerings) => $offerings->firstWhere('id', $offering->id)->approved_count === 1
            && $offerings->firstWhere('id', $offering->id)->pre_registrations_count === 1)
        ->assertViewHas('groupPanels', fn ($panels) => $panels[$offering->id]->first()['student_list']->contains(
            fn ($row) => $row['id'] === $student->id && $row['status'] === 'approved'
        ));
    $this->actingAs($student)->get(route('student.course-requests.index'))
        ->assertOk()->assertSee('Approved — student registered')->assertSee('Additional offering approved.');
    $this->get(route('student.pre-registration'))->assertOk()->assertSee($course->code);
    $this->get(route('student.slip'))->assertOk()->assertSee($course->code);
});

test('the request picker includes flexible plan electives but excludes offered and unrelated courses', function () {
    [$student, , , $course, $plan] = courseRequestFixture();
    Offering::create(['course_id' => $course->id, 'session' => '2026/2027', 'semester' => '1', 'is_offered' => true]);
    $elective = Course::create(['code' => 'CRQ 29903', 'name' => 'Flexible elective', 'credits' => 3]);
    PlanCourse::create([
        'course_plan_id' => $plan->id, 'course_id' => $elective->id,
        'year' => null, 'semester' => null, 'category' => 'elective',
    ]);
    Course::create(['code' => 'OTHER 101', 'name' => 'Unrelated course', 'credits' => 3]);

    $this->actingAs($student)->get(route('student.course-requests.index'))
        ->assertOk()->assertViewHas('courses', fn ($courses) => $courses->pluck('id')->all() === [$elective->id]);
});

test('invalid course requests do not reach the advisor or publish an offering', function (string $condition, string $field) {
    [$student, , , $course] = courseRequestFixture();
    $reason = 'Needed for graduation.';
    if ($condition === 'offered') {
        Offering::create(['course_id' => $course->id, 'session' => '2026/2027', 'semester' => '1', 'is_offered' => true]);
    } elseif ($condition === 'unrelated') {
        $course->update(['course_plan_id' => null]);
    } elseif ($condition === 'inactive') {
        $course->update(['is_active' => false]);
    } elseif (in_array($condition, ['COMPLETED', 'EXEMPTED'], true)) {
        StudyPlanEntry::create([
            'user_id' => $student->id, 'course_id' => $course->id,
            'year' => 1, 'semester' => 1, 'status' => $condition,
        ]);
    } elseif ($condition === 'no advisor') {
        $student->update(['advisor_id' => null]);
    } elseif ($condition === 'blank reason') {
        $reason = '';
    } elseif ($condition === 'too long') {
        $reason = str_repeat('x', 2001);
    } elseif ($condition === 'closed') {
        Setting::put(RegistrationSchedule::REGISTRATION_CUTOFF_KEY, now()->subDay()->toDateString());
    }

    $this->actingAs($student)->post(route('student.course-requests.store'), [
        'course_id' => $course->id, 'reason' => $reason,
    ])->assertSessionHasErrors($field);

    $this->assertDatabaseCount('course_offering_requests', 0);
    $this->assertDatabaseCount('offerings', $condition === 'offered' ? 1 : 0);
})->with([
    ['offered', 'course_id'],
    ['unrelated', 'course_id'],
    ['inactive', 'course_id'],
    ['COMPLETED', 'course_id'],
    ['EXEMPTED', 'course_id'],
    ['no advisor', 'course_id'],
    ['blank reason', 'reason'],
    ['too long', 'reason'],
    ['closed', 'course_request'],
]);

test('duplicate requests for the same course and term are rejected', function () {
    [$student, , , $course] = courseRequestFixture();
    pendingCourseRequest($student, $course);

    $this->actingAs($student)->post(route('student.course-requests.store'), [
        'course_id' => $course->id, 'reason' => 'Second attempt.',
    ])->assertSessionHasErrors('course_id');

    $this->assertDatabaseCount('course_offering_requests', 1);
});

test('new requests enforce the combined registration and outstanding request credit limit', function (int $registeredCredits, int $requestedCredits, string $status, bool $allowed) {
    [$student, , , $course] = courseRequestFixture();
    if ($registeredCredits > 0) {
        $registeredCourse = Course::create(['code' => 'LOAD101', 'name' => 'Existing registration', 'credits' => $registeredCredits]);
        $offering = Offering::create(['course_id' => $registeredCourse->id, 'session' => '2026/2027', 'semester' => '1']);
        PreRegistration::create([
            'user_id' => $student->id, 'offering_id' => $offering->id,
            'session' => '2026/2027', 'semester' => '1', 'credits' => $registeredCredits, 'status' => 'pending',
        ]);
    }
    if ($requestedCredits > 0) {
        $requestedCourse = Course::create(['code' => 'LOAD202', 'name' => 'Outstanding request', 'credits' => $requestedCredits]);
        pendingCourseRequest($student, $requestedCourse)->update(['status' => $status]);
    }
    $before = CourseOfferingRequest::count();

    $response = $this->actingAs($student)->post(route('student.course-requests.store'), [
        'course_id' => $course->id, 'reason' => 'Needed for graduation.',
    ]);

    if ($allowed) {
        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('course_offering_requests', $before + 1);
    } else {
        $response->assertSessionHasErrors('course_id');
        $this->assertDatabaseCount('course_offering_requests', $before);
    }
})->with([
    'registrations alone exceed cap' => [18, 0, 'pending_advisor', false],
    'pending advisor request exceeds cap' => [15, 3, 'pending_advisor', false],
    'pending HoD request exceeds cap' => [15, 3, 'pending_hod', false],
    'approved unregistered request reserves credits' => [15, 3, 'approved', false],
    'exactly 20 is allowed' => [14, 3, 'pending_advisor', true],
    'rejected request releases credits' => [17, 3, 'rejected', true],
    'requests alone exceed cap' => [0, 18, 'pending_hod', false],
]);

test('registered request credits are not counted twice and requests from another term do not reserve credits', function () {
    [$student, , , $course] = courseRequestFixture();
    $existing = Course::create(['code' => 'LOAD17', 'name' => 'Registered requested course', 'credits' => 17]);
    $offering = Offering::create(['course_id' => $existing->id, 'session' => '2026/2027', 'semester' => '1']);
    PreRegistration::create([
        'user_id' => $student->id, 'offering_id' => $offering->id,
        'session' => '2026/2027', 'semester' => '1', 'credits' => 17, 'status' => 'approved',
    ]);
    pendingCourseRequest($student, $existing)->update(['status' => 'approved']);
    $oldCourse = Course::create(['code' => 'OLD20', 'name' => 'Old request', 'credits' => 20]);
    pendingCourseRequest($student, $oldCourse)->update(['session' => '2025/2026']);

    $this->actingAs($student)->post(route('student.course-requests.store'), [
        'course_id' => $course->id, 'reason' => 'Final course needed.',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->get(route('student.course-requests.index'))->assertOk()->assertViewHas('committedCredits', 20);
});

test('normal pre-registration cannot consume credits reserved by course requests', function () {
    [$student, , , $course] = courseRequestFixture();
    pendingCourseRequest($student, $course);
    $other = Course::create(['code' => 'OTHER18', 'name' => 'Offered course', 'credits' => 18]);
    $offering = Offering::create(['course_id' => $other->id, 'session' => '2026/2027', 'semester' => '1', 'is_offered' => true]);

    $this->actingAs($student)->post(route('student.pre-registration.store', $offering))
        ->assertSessionHasErrors('offering');

    $this->assertDatabaseCount('pre_registrations', 0);
});

test('pre-registering a requested course uses its reservation without adding its credits twice', function () {
    [$student, , , $course] = courseRequestFixture();
    pendingCourseRequest($student, $course, true);
    $other = Course::create(['code' => 'OTHER17', 'name' => 'Existing course', 'credits' => 17]);
    $otherOffering = Offering::create(['course_id' => $other->id, 'session' => '2026/2027', 'semester' => '1']);
    PreRegistration::create([
        'user_id' => $student->id, 'offering_id' => $otherOffering->id,
        'session' => '2026/2027', 'semester' => '1', 'credits' => 17, 'status' => 'approved',
    ]);
    $offering = Offering::create(['course_id' => $course->id, 'session' => '2026/2027', 'semester' => '1', 'is_offered' => true]);

    $this->actingAs($student)->get(route('student.pre-registration'))
        ->assertOk()->assertViewHas('rows', fn ($rows) => collect($rows)->first()['addable'] === true);
    $this->post(route('student.pre-registration.store', $offering))->assertRedirect()->assertSessionHasNoErrors();

    $this->get(route('student.course-requests.index'))->assertOk()->assertViewHas('committedCredits', 20);
    $this->assertDatabaseCount('pre_registrations', 2);
});

test('advisors must provide a reason for both forwarding and rejecting', function (string $decision, ?string $note) {
    [$student, $advisor, , $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course);

    $this->actingAs($advisor)->post(route('advisor.course-requests.review', $record), [
        'decision' => $decision, 'note' => $note,
    ])->assertSessionHasErrors(['note' => 'Please provide your reason for forwarding or rejecting this request.']);

    expect($record->refresh()->status)->toBe('pending_advisor');
    expect($record->advisor_reviewed_at)->toBeNull();
})->with([
    'forward without reason' => ['forward', null],
    'forward whitespace' => ['forward', '   '],
    'reject without reason' => ['reject', null],
    'reject whitespace' => ['reject', '   '],
]);

test('student reasons cannot be whitespace only', function () {
    [$student, , , $course] = courseRequestFixture();

    $this->actingAs($student)->post(route('student.course-requests.store'), [
        'course_id' => $course->id, 'reason' => '   ',
    ])->assertSessionHasErrors(['reason' => 'Please provide a reason for requesting this course.']);

    $this->assertDatabaseCount('course_offering_requests', 0);
});

test('only a students assigned advisor can forward their request', function () {
    [$student, , , $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course);
    $other = User::factory()->create(['role' => 'advisor']);

    $this->actingAs($other)->get(route('advisor.course-requests.index'))
        ->assertOk()->assertViewHas('requests', fn ($requests) => $requests->isEmpty());
    $this->post(route('advisor.course-requests.review', $record), ['decision' => 'forward'])
        ->assertForbidden();
    expect($record->refresh()->status)->toBe('pending_advisor');
});

test('an advisor can reject with feedback without forwarding to the HoD', function () {
    [$student, $advisor, $hod, $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course);

    $this->actingAs($advisor)->post(route('advisor.course-requests.review', $record), [
        'decision' => 'reject', 'note' => 'Please complete the prerequisite first.',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($record->refresh()->status)->toBe('rejected');
    expect($record->forwarded_at)->toBeNull();
    $this->actingAs($hod)->get(route('advisor.course-requests.hod.index'))
        ->assertOk()->assertViewHas('requests', fn ($requests) => $requests->isEmpty());
    $this->actingAs($student)->get(route('student.course-requests.index'))
        ->assertOk()->assertSee('Please complete the prerequisite first.');
    $this->assertDatabaseCount('offerings', 0);
});

test('rejection at either review stage requires a note', function (bool $forwarded) {
    [$student, $advisor, $hod, $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course, $forwarded);
    $originalStatus = $record->status;

    $this->actingAs($forwarded ? $hod : $advisor)->post(
        route($forwarded ? 'advisor.course-requests.hod.review' : 'advisor.course-requests.review', $record),
        ['decision' => 'reject']
    )->assertSessionHasErrors('note');

    expect($record->refresh()->status)->toBe($originalStatus);
    $this->assertDatabaseCount('offerings', 0);
})->with([false, true]);

test('a head of department can reject a forwarded request without offering the course', function () {
    [$student, , $hod, $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course, true);

    $this->actingAs($hod)->post(route('advisor.course-requests.hod.review', $record), [
        'decision' => 'reject', 'note' => 'Insufficient teaching capacity.',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('course_offering_requests', [
        'id' => $record->id, 'status' => 'rejected', 'hod_reviewed_by' => $hod->id,
    ]);
    $this->assertDatabaseCount('offerings', 0);
    $this->actingAs($student)->get(route('student.course-requests.index'))
        ->assertOk()->assertSee('Rejected by Head of Department')->assertSee('Insufficient teaching capacity.');
});

test('the HoD cannot skip advisor review or re-review a final decision', function (string $status) {
    [$student, , $hod, $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course);
    $record->update(['status' => $status]);

    $this->actingAs($hod)->post(route('advisor.course-requests.hod.review', $record), ['decision' => 'approve'])
        ->assertSessionHasErrors('course_request');

    expect($record->refresh()->status)->toBe($status);
    $this->assertDatabaseCount('offerings', 0);
})->with(['pending_advisor', 'pending_hod', 'approved', 'rejected']);

test('final approval cannot publish an old term or a course after the registration deadline', function (string $condition) {
    [$student, , $hod, $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course, true);
    if ($condition === 'old term') {
        $record->update(['session' => '2025/2026']);
    } elseif ($condition === 'inactive') {
        $course->update(['is_active' => false]);
    } else {
        Setting::put(RegistrationSchedule::REGISTRATION_CUTOFF_KEY, now()->subDay()->toDateString());
    }

    $this->actingAs($hod)->post(route('advisor.course-requests.hod.review', $record), ['decision' => 'approve'])
        ->assertSessionHasErrors('course_request');

    expect($record->refresh()->status)->toBe('pending_hod');
    $this->assertDatabaseCount('offerings', 0);
})->with(['old term', 'closed', 'inactive']);

test('approval reuses an existing offering and preserves lecturer and section settings', function () {
    [$student, $advisor, $hod, $course] = courseRequestFixture();
    Setting::put(RegistrationSchedule::OFFERING_CUTOFF_KEY, now()->subDay()->toDateString());
    Setting::put(RegistrationSchedule::REGISTRATION_CUTOFF_KEY, now()->addDay()->toDateString());
    $record = pendingCourseRequest($student, $course, true);
    $offering = Offering::create([
        'course_id' => $course->id, 'session' => '2026/2027', 'semester' => '1',
        'lecturer_id' => $advisor->id, 'section' => '03', 'capacity' => 25, 'is_offered' => false,
    ]);

    $this->actingAs($hod)->post(route('advisor.course-requests.hod.review', $record), ['decision' => 'approve'])
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseCount('offerings', 1);
    $this->assertDatabaseHas('offerings', [
        'id' => $offering->id, 'is_offered' => true, 'lecturer_id' => $advisor->id, 'section' => '03', 'capacity' => 25,
    ]);
    $this->assertDatabaseHas('pre_registrations', [
        'user_id' => $student->id, 'offering_id' => $offering->id, 'status' => 'approved',
    ]);
});

test('two students requests for the same course create only one offered course', function () {
    [$student, $advisor, $hod, $course, $plan] = courseRequestFixture();
    $other = User::factory()->create(['role' => 'student', 'advisor_id' => $advisor->id, 'plan_id' => $plan->id]);
    $records = [
        pendingCourseRequest($student, $course, true),
        pendingCourseRequest($other, $course, true),
    ];

    foreach ($records as $record) {
        $this->actingAs($hod)->post(route('advisor.course-requests.hod.review', $record), ['decision' => 'approve'])
            ->assertRedirect()->assertSessionHasNoErrors();
    }

    $this->assertDatabaseCount('offerings', 1);
    $this->assertDatabaseCount('pre_registrations', 2);
    expect(CourseOfferingRequest::where('status', 'approved')->count())->toBe(2);
});

test('HoD approval upgrades an existing pending registration without double counting credits or approving other courses', function () {
    [$student, , $hod, $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course, true);
    $offering = Offering::create(['course_id' => $course->id, 'session' => '2026/2027', 'semester' => '1']);
    $registration = PreRegistration::create([
        'user_id' => $student->id, 'offering_id' => $offering->id,
        'session' => '2026/2027', 'semester' => '1', 'credits' => 3, 'status' => 'pending', 'section' => '05',
    ]);
    $otherCourse = Course::create(['code' => 'OTHER17', 'name' => 'Other course', 'credits' => 17]);
    $otherOffering = Offering::create(['course_id' => $otherCourse->id, 'session' => '2026/2027', 'semester' => '1']);
    $otherRegistration = PreRegistration::create([
        'user_id' => $student->id, 'offering_id' => $otherOffering->id,
        'session' => '2026/2027', 'semester' => '1', 'credits' => 17, 'status' => 'pending',
    ]);
    $submission = RegistrationSubmission::create([
        'user_id' => $student->id, 'session' => '2026/2027', 'semester' => '1', 'status' => 'submitted',
    ]);

    $this->actingAs($hod)->post(route('advisor.course-requests.hod.review', $record), ['decision' => 'approve'])
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseCount('pre_registrations', 2);
    expect($registration->refresh()->status)->toBe('approved');
    expect($registration->section)->toBe('05');
    expect($otherRegistration->refresh()->status)->toBe('pending');
    expect($submission->refresh()->status)->toBe('submitted');
});

test('automatic registration warns about prerequisites but still enforces the credit cap atomically', function (string $condition) {
    [$student, , $hod, $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course, true);
    if ($condition === 'prerequisite') {
        $course->update(['prerequisites' => 'PRE101']);
        $this->actingAs($hod)->get(route('advisor.course-requests.hod.index'))->assertOk()
            ->assertSee('Prerequisite warning — not yet fulfilled')->assertSee('PRE101 · Not completed');
        $this->post(route('advisor.course-requests.hod.review', $record), ['decision' => 'approve'])
            ->assertRedirect()->assertSessionHasNoErrors();

        expect($record->refresh()->status)->toBe('approved');
        $this->assertDatabaseHas('pre_registrations', ['user_id' => $student->id, 'status' => 'approved']);

        return;
    } else {
        $other = Course::create(['code' => 'OTHER18', 'name' => 'Other course', 'credits' => 18]);
        $offering = Offering::create(['course_id' => $other->id, 'session' => '2026/2027', 'semester' => '1']);
        PreRegistration::create([
            'user_id' => $student->id, 'offering_id' => $offering->id,
            'session' => '2026/2027', 'semester' => '1', 'credits' => 18, 'status' => 'approved',
        ]);
    }

    $this->actingAs($hod)->post(route('advisor.course-requests.hod.review', $record), ['decision' => 'approve'])
        ->assertSessionHasErrors('course_request');

    expect($record->refresh()->status)->toBe('pending_hod');
    expect($record->hod_reviewed_by)->toBeNull();
    $this->assertDatabaseMissing('offerings', ['course_id' => $course->id]);
    $this->assertDatabaseCount('pre_registrations', $condition === 'prerequisite' ? 0 : 1);
})->with(['prerequisite', 'credit cap']);

test('approval and repeated review do not duplicate an already approved registration', function () {
    [$student, $advisor, $hod, $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course, true);
    $offering = Offering::create(['course_id' => $course->id, 'session' => '2026/2027', 'semester' => '1']);
    $registration = PreRegistration::create([
        'user_id' => $student->id, 'offering_id' => $offering->id,
        'session' => '2026/2027', 'semester' => '1', 'credits' => 3,
        'status' => 'approved', 'section' => '02', 'reviewed_by' => $advisor->id,
    ]);

    $this->actingAs($hod)->post(route('advisor.course-requests.hod.review', $record), ['decision' => 'approve'])
        ->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('advisor.course-requests.hod.review', $record), ['decision' => 'approve'])
        ->assertSessionHasErrors('course_request');

    $this->assertDatabaseCount('pre_registrations', 1);
    expect($registration->refresh()->section)->toBe('02');
    expect($registration->reviewed_by)->toBe($advisor->id);
    expect($record->refresh()->status)->toBe('approved');
});

test('previously approved requests can be reconciled safely and without duplicates', function () {
    [$student, , $hod, $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course, true);
    $record->update(['status' => 'approved', 'hod_reviewed_by' => $hod->id, 'hod_reviewed_at' => now()]);
    $pending = User::factory()->create(['role' => 'student', 'advisor_id' => $student->advisor_id]);
    pendingCourseRequest($pending, $course, true);

    $this->artisan('course-requests:sync-registrations', ['--dry-run' => true])->assertSuccessful();
    $this->assertDatabaseCount('pre_registrations', 0);
    $this->artisan('course-requests:sync-registrations')->assertSuccessful();
    $this->artisan('course-requests:sync-registrations')->assertSuccessful();

    $this->assertDatabaseCount('pre_registrations', 1);
    $this->assertDatabaseHas('pre_registrations', ['user_id' => $student->id, 'status' => 'approved', 'reviewed_by' => $hod->id]);
    $this->assertDatabaseMissing('pre_registrations', ['user_id' => $pending->id]);
});

test('unregistered historical approvals remain pending registration when the credit limit blocks synchronization', function () {
    [$student, , $hod, $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course, true);
    $record->update(['status' => 'approved', 'hod_reviewed_by' => $hod->id, 'hod_reviewed_at' => now()]);
    $other = Course::create(['code' => 'OLD18', 'name' => 'Existing load', 'credits' => 18]);
    $offering = Offering::create(['course_id' => $other->id, 'session' => '2026/2027', 'semester' => '1']);
    PreRegistration::create([
        'user_id' => $student->id, 'offering_id' => $offering->id,
        'session' => '2026/2027', 'semester' => '1', 'credits' => 18, 'status' => 'approved',
    ]);

    $this->artisan('course-requests:sync-registrations')->assertFailed();

    $this->assertDatabaseCount('pre_registrations', 1);
    $this->assertDatabaseMissing('offerings', ['course_id' => $course->id]);
    $this->actingAs($student)->get(route('student.course-requests.index'))
        ->assertOk()->assertSee('Approved — registration pending')
        ->assertDontSee('Approved — student registered');
});

test('student request history is private and renders notes safely', function () {
    [$student, , , $course] = courseRequestFixture();
    $other = User::factory()->create(['role' => 'student']);
    $record = pendingCourseRequest($student, $course);
    $record->update(['reason' => '<script>alert("unsafe")</script>']);
    CourseOfferingRequest::factory()->create(['user_id' => $other->id, 'course_id' => $course->id, 'reason' => 'Private other reason']);

    $this->actingAs($student)->get(route('student.course-requests.index'))
        ->assertOk()->assertSee('<script>alert("unsafe")</script>')
        ->assertDontSee('<script>alert("unsafe")</script>', false)
        ->assertDontSee('Private other reason');
});

test('students and ordinary advisors cannot perform HoD approval', function (string $role) {
    [$student, $advisor, , $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course, true);

    $this->actingAs($role === 'student' ? $student : $advisor)
        ->post(route('advisor.course-requests.hod.review', $record), ['decision' => 'approve'])
        ->assertForbidden();

    expect($record->refresh()->status)->toBe('pending_hod');
    $this->assertDatabaseCount('offerings', 0);
})->with(['student', 'advisor']);

test('students cannot review requests and staff cannot submit as students', function () {
    [$student, $advisor, , $course] = courseRequestFixture();
    $record = pendingCourseRequest($student, $course);

    $this->actingAs($student)->post(route('advisor.course-requests.review', $record), ['decision' => 'forward'])
        ->assertForbidden();
    $this->actingAs($advisor)->post(route('student.course-requests.store'), [
        'course_id' => $course->id, 'reason' => 'Not a student.',
    ])->assertForbidden();

    $this->assertDatabaseCount('course_offering_requests', 1);
    expect($record->refresh()->status)->toBe('pending_advisor');
});

test('administrators can perform final approval but still require advisor forwarding', function () {
    [$student, , , $course] = courseRequestFixture();
    $admin = User::factory()->create(['role' => 'admin']);
    $record = pendingCourseRequest($student, $course, true);

    $this->actingAs($admin)->get(route('advisor.course-requests.hod.index'))->assertOk();
    $this->post(route('advisor.course-requests.hod.review', $record), ['decision' => 'approve'])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($record->refresh()->status)->toBe('approved');
    expect($record->hod_reviewed_by)->toBe($admin->id);
});

test('guests must sign in to request or review courses', function () {
    $this->get(route('student.course-requests.index'))->assertRedirect(route('login'));
    $this->post(route('student.course-requests.store'))->assertRedirect(route('login'));
    $this->get(route('advisor.course-requests.index'))->assertRedirect(route('login'));
    $this->get(route('advisor.course-requests.hod.index'))->assertRedirect(route('login'));
});
