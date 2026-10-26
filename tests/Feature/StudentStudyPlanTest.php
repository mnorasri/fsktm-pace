<?php

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\Program;
use App\Models\StudyPlanEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Build a student whose programme plan carries real curriculum courses.
 *
 * @return array{0: User, 1: CoursePlan, 2: array<int, Course>}
 */
function makeStudentWithCurriculum(int $credits = 3): array
{
    $program = Program::create(['code' => 'TST', 'name' => 'Test Programme']);
    $plan = CoursePlan::create([
        'program_id' => $program->id,
        'code' => 'TST2025-LOCAL',
        'name' => 'Test Study Plan',
    ]);

    $courses = [
        Course::create([
            'code' => 'TST 10103', 'name' => 'First Course', 'credits' => $credits,
            'semester' => 1, 'course_plan_id' => $plan->id,
        ]),
        Course::create([
            'code' => 'TST 10203', 'name' => 'Second Course', 'credits' => $credits,
            'semester' => 2, 'course_plan_id' => $plan->id,
        ]),
    ];

    $student = User::factory()->create([
        'role' => 'student',
        'program_id' => $program->id,
        'plan_id' => $plan->id,
    ]);

    return [$student, $plan, $courses];
}

test('a study plan row with neither a course nor a placeholder no longer breaks the page', function () {
    [$student, $plan] = makeStudentWithCurriculum();

    // Incomplete curriculum data — this is what used to raise
    // "Attempt to read property 'code' on null" in the study plan view.
    StudyPlanEntry::create([
        'user_id' => $student->id,
        'course_plan_id' => $plan->id,
        'year' => 1,
        'semester' => 1,
        'status' => 'PLANNED',
    ]);

    $this->actingAs($student)
        ->get(route('student.study-plan'))
        ->assertOk();
});

test('the plan is seeded from the curriculum and empty rows are cleared', function () {
    [$student, $plan, $courses] = makeStudentWithCurriculum();

    StudyPlanEntry::create([
        'user_id' => $student->id,
        'course_plan_id' => $plan->id,
        'year' => 1,
        'semester' => 1,
        'status' => 'PLANNED',
    ]);

    $response = $this->actingAs($student)->get(route('student.study-plan'));

    $response->assertOk()
        ->assertSee($courses[0]->code)
        ->assertSee($courses[1]->code);

    $entries = StudyPlanEntry::where('user_id', $student->id)->get();

    expect($entries->whereNull('course_id'))->toHaveCount(0)
        ->and($entries->pluck('course_id')->all())->toEqualCanonicalizing([
            $courses[0]->id,
            $courses[1]->id,
        ]);

    // Semester 1 sits in Year 1 Semester 1, Semester 2 in Year 1 Semester 2.
    expect($entries->firstWhere('course_id', $courses[0]->id)->only(['year', 'semester']))
        ->toBe(['year' => 1, 'semester' => 1])
        ->and($entries->firstWhere('course_id', $courses[1]->id)->only(['year', 'semester']))
        ->toBe(['year' => 1, 'semester' => 2]);
});

test('placeholder rows from the plan are kept as elective slots', function () {
    [$student, $plan] = makeStudentWithCurriculum();

    StudyPlanEntry::create([
        'user_id' => $student->id,
        'course_plan_id' => $plan->id,
        'placeholder_key' => 'REPLACE_ELECTIVE_1',
        'year' => 2,
        'semester' => 1,
        'status' => 'PLANNED',
    ]);

    $this->actingAs($student)
        ->get(route('student.study-plan'))
        ->assertOk()
        ->assertSee('REPLACE_ELECTIVE_1');

    // Existing entries mean the plan is considered seeded, so the curriculum
    // courses are not pushed in on top of the placeholder.
    expect(StudyPlanEntry::where('user_id', $student->id)->count())->toBe(1);
});

test('the grid keeps two columns when no special semester is used', function () {
    [$student] = makeStudentWithCurriculum();

    $response = $this->actingAs($student)->get(route('student.study-plan'))->assertOk();

    expect($response->getContent())->toContain('lg:grid-cols-2');
});

test('a special semester column sits between the odd and even semesters', function () {
    [$student, $plan, $courses] = makeStudentWithCurriculum();

    StudyPlanEntry::create([
        'user_id' => $student->id, 'course_plan_id' => $plan->id, 'course_id' => $courses[0]->id,
        'year' => 1, 'semester' => 1, 'status' => 'PLANNED',
    ]);
    StudyPlanEntry::create([
        'user_id' => $student->id, 'course_plan_id' => $plan->id, 'course_id' => $courses[1]->id,
        'year' => 1, 'semester' => 2, 'status' => 'PLANNED',
    ]);
    StudyPlanEntry::create([
        'user_id' => $student->id, 'course_plan_id' => $plan->id, 'placeholder_key' => 'REPLACE_ELECTIVE_1',
        'year' => 1, 'semester' => 3, 'status' => 'PLANNED',
    ]);

    $response = $this->actingAs($student)->get(route('student.study-plan'))->assertOk();

    expect($response->getContent())->toContain('lg:grid-cols-3');
    $response->assertSeeInOrder(['Semester 1', 'Special Semester', 'Semester 2']);
});

test('moving a course only changes the year — the semester parity stays put', function () {
    [$student, $plan, $courses] = makeStudentWithCurriculum();

    $this->actingAs($student)->get(route('student.study-plan'))->assertOk();

    $entry = StudyPlanEntry::where('user_id', $student->id)->where('course_id', $courses[0]->id)->firstOrFail();

    // Semester 1 is odd, so it can never become the even semester.
    $this->actingAs($student)
        ->put(route('student.study-plan.term', $entry), ['term' => '3:2'])
        ->assertSessionHasErrors('term');

    expect($entry->fresh()->only(['year', 'semester']))->toBe(['year' => 1, 'semester' => 1]);

    $this->actingAs($student)
        ->put(route('student.study-plan.term', $entry), ['term' => '3:1'])
        ->assertSessionHasNoErrors();

    expect($entry->fresh()->only(['year', 'semester']))->toBe(['year' => 3, 'semester' => 1]);
});

test('industrial training may be moved into either semester', function () {
    [$student, $plan] = makeStudentWithCurriculum();

    $type = CourseType::firstOrCreate(['code' => 'CT06'], ['name' => 'Industrial Training']);

    $training = Course::create([
        'code' => 'TST 41812', 'name' => 'Industrial Training', 'credits' => 12,
        'semester' => 7, 'course_plan_id' => $plan->id, 'course_type_code' => $type->code,
    ]);

    $this->actingAs($student)->get(route('student.study-plan'))->assertOk();

    // Semester 7 is odd, so the seeding puts it in Year 4 Semester 1.
    $entry = StudyPlanEntry::where('user_id', $student->id)->where('course_id', $training->id)->firstOrFail();
    expect($entry->only(['year', 'semester']))->toBe(['year' => 4, 'semester' => 1]);

    // Unlike other courses it is allowed to cross into the even semester.
    $this->actingAs($student)
        ->put(route('student.study-plan.term', $entry), ['term' => '4:2'])
        ->assertSessionHasNoErrors();

    expect($entry->fresh()->only(['year', 'semester']))->toBe(['year' => 4, 'semester' => 2]);
});

test('a course that is already completed cannot be moved', function () {
    [$student, $plan, $courses] = makeStudentWithCurriculum();

    $this->actingAs($student)->get(route('student.study-plan'))->assertOk();

    $entry = StudyPlanEntry::where('user_id', $student->id)->where('course_id', $courses[0]->id)->firstOrFail();
    $entry->update(['status' => 'COMPLETED']);

    $this->actingAs($student)
        ->put(route('student.study-plan.term', $entry), ['term' => '2:1'])
        ->assertSessionHasErrors('term');

    expect($entry->fresh()->year)->toBe(1);
});

test('an either/or group is planned once and can be switched', function () {
    [$student, $plan] = makeStudentWithCurriculum();

    $islam = Course::create([
        'code' => 'TST 20102', 'name' => 'Pengajian Islam', 'credits' => 2,
        'semester' => 1, 'course_plan_id' => $plan->id, 'alternative_group' => 'Pengajian',
    ]);
    $moral = Course::create([
        'code' => 'TST 20202', 'name' => 'Pengajian Moral', 'credits' => 2,
        'semester' => 1, 'course_plan_id' => $plan->id, 'alternative_group' => 'Pengajian',
    ]);

    $this->actingAs($student)->get(route('student.study-plan'))->assertOk();

    // Both options sit in the curriculum, but only the default (lowest code)
    // is planned.
    $entries = StudyPlanEntry::where('user_id', $student->id)->get();
    $planned = $entries->pluck('course_id')->all();

    expect($planned)->toContain($islam->id)
        ->and($planned)->not->toContain($moral->id);

    $before = $entries->firstWhere('course_id', $islam->id);

    $this->actingAs($student)
        ->put(route('student.study-plan.alternative'), ['group' => 'Pengajian', 'course_id' => $moral->id])
        ->assertSessionHasNoErrors();

    $after = StudyPlanEntry::where('user_id', $student->id)->get();
    $plannedAfter = $after->pluck('course_id')->all();

    expect($plannedAfter)->toContain($moral->id)
        ->and($plannedAfter)->not->toContain($islam->id)
        ->and($after)->toHaveCount($entries->count());

    // The replacement takes over the term the group already occupied.
    expect($after->firstWhere('course_id', $moral->id)->only(['year', 'semester']))
        ->toBe($before->only(['year', 'semester']));

    // A group the student must pick from cannot be emptied.
    $this->actingAs($student)
        ->delete(route('student.study-plan.remove', $after->firstWhere('course_id', $moral->id)))
        ->assertSessionHasErrors('entry');

    expect(StudyPlanEntry::where('user_id', $student->id)->where('course_id', $moral->id)->exists())->toBeTrue();
});
