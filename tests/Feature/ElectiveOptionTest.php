<?php

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\Program;
use App\Models\StudyPlanEntry;
use App\Models\User;
use App\Services\StudyPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A student whose plan carries an "Elective Option" slot (type CT07A) and the
 * "Elective Course" catalogue (type CT07) it is filled from.
 *
 * @return array{0: User, 1: CoursePlan, 2: Course, 3: array<int, Course>}
 */
function makeStudentWithElectives(): array
{
    $program = Program::create(['code' => 'TS2', 'name' => 'Elective Test Programme']);
    $plan = CoursePlan::create([
        'program_id' => $program->id,
        'code' => 'TS22025-LOCAL',
        'name' => 'Elective Study Plan',
    ]);

    $required = CourseType::create(['code' => 'CT02', 'name' => 'Computing Core']);
    $option = CourseType::create(['code' => CourseType::ELECTIVE_OPTION, 'name' => 'Elective Option']);
    $pool = CourseType::create(['code' => CourseType::ELECTIVE_COURSE, 'name' => 'Elective Course']);

    Course::create([
        'code' => 'TS2 10103', 'name' => 'Core Course', 'credits' => 3,
        'semester' => 1, 'course_plan_id' => $plan->id, 'course_type_code' => $required->code,
    ]);

    // Semester 4 → the slot is seeded into Year 2 Semester 2.
    $slot = Course::create([
        'code' => 'TS* 3**03-1', 'name' => 'Elektif I', 'credits' => 3,
        'semester' => 4, 'course_plan_id' => $plan->id, 'course_type_code' => $option->code,
    ]);

    Course::create([
        'code' => 'TS* 3**03-2', 'name' => 'Elektif II', 'credits' => 3,
        'semester' => 5, 'course_plan_id' => $plan->id, 'course_type_code' => $option->code,
    ]);

    $electives = [
        Course::create([
            'code' => 'TS2 30603', 'name' => 'DotNet Programming', 'credits' => 3,
            'semester' => 0, 'course_plan_id' => $plan->id, 'course_type_code' => $pool->code,
        ]),
        Course::create([
            'code' => 'TS2 34303', 'name' => 'Machine Learning', 'credits' => 3,
            'semester' => 0, 'course_plan_id' => $plan->id, 'course_type_code' => $pool->code,
        ]),
    ];

    $student = User::factory()->create([
        'role' => 'student',
        'program_id' => $program->id,
        'plan_id' => $plan->id,
    ]);

    return [$student, $plan, $slot, $electives];
}

/**
 * The plan entry standing for the given Elective Option slot. Seeding happens
 * the first time a study-plan page loads, so ask the service for it first.
 */
function electiveSlotEntry(User $student, Course $slot): StudyPlanEntry
{
    app(StudyPlanService::class)->entriesFor($student);

    return StudyPlanEntry::where('user_id', $student->id)
        ->where('course_id', $slot->id)
        ->firstOrFail();
}

test('the elective catalogue is listed in a panel instead of being seeded into a term', function () {
    [$student, , , $electives] = makeStudentWithElectives();

    $response = $this->actingAs($student)->get(route('student.study-plan'))->assertOk();

    // The panel after the last year lists the catalogue.
    $response->assertSee('Elective Course')
        ->assertSee($electives[0]->code)
        ->assertSee($electives[1]->code)
        ->assertSee('Available');

    // The electives are not plan rows of their own any more — they only reach
    // the plan through an Elective Option slot.
    expect(StudyPlanEntry::where('user_id', $student->id)
        ->whereIn('course_id', collect($electives)->pluck('id'))
        ->exists())->toBeFalse();
});

test('an elective option slot offers a dropdown of the plan elective courses', function () {
    [$student, , $slot, $electives] = makeStudentWithElectives();

    $entry = electiveSlotEntry($student, $slot);

    $this->actingAs($student)
        ->get(route('student.study-plan'))
        ->assertOk()
        ->assertSee('Elektif I')
        ->assertSee('No course chosen')
        ->assertSee('data-auto-submit', false)
        ->assertSee(route('student.study-plan.elective', $entry), false)
        ->assertSee('value="'.$electives[0]->id.'"', false)
        ->assertSee('value="'.$electives[1]->id.'"', false)
        ->assertSee('— not chosen —');
});

test('choosing an elective fills the slot and keeps its term', function () {
    [$student, , $slot, $electives] = makeStudentWithElectives();

    $entry = electiveSlotEntry($student, $slot);
    $before = $entry->only(['year', 'semester']);

    $this->actingAs($student)
        ->put(route('student.study-plan.elective', $entry), ['course_id' => $electives[1]->id])
        ->assertSessionHasNoErrors();

    $entry->refresh();

    expect($entry->course_id)->toBe($electives[1]->id)
        ->and($entry->elective_slot_id)->toBe($slot->id)
        ->and($entry->only(['year', 'semester']))->toBe($before)
        ->and($entry->isElectiveOption())->toBeTrue()
        ->and($entry->hasElectiveChoice())->toBeTrue();

    // The row still names the slot but shows the course inside it, and the
    // catalogue panel reports where the course went.
    $this->actingAs($student)
        ->get(route('student.study-plan'))
        ->assertOk()
        ->assertSee('Elektif I')
        ->assertSee($electives[1]->code)
        ->assertSee('Chosen for Elektif I');
});

test('an elective course from another programme cannot fill the slot', function () {
    [$student, , $slot] = makeStudentWithElectives();

    $otherPlan = CoursePlan::create([
        'program_id' => Program::create(['code' => 'TS3', 'name' => 'Other Programme'])->id,
        'code' => 'TS32025-LOCAL',
        'name' => 'Other Study Plan',
    ]);

    $foreign = Course::create([
        'code' => 'TS3 30603', 'name' => 'Foreign Elective', 'credits' => 3,
        'semester' => 0, 'course_plan_id' => $otherPlan->id,
        'course_type_code' => CourseType::where('code', CourseType::ELECTIVE_COURSE)->value('code'),
    ]);

    $entry = electiveSlotEntry($student, $slot);

    $this->actingAs($student)
        ->put(route('student.study-plan.elective', $entry), ['course_id' => $foreign->id])
        ->assertSessionHasErrors('course_id');

    expect($entry->fresh()->course_id)->toBe($slot->id)
        ->and($entry->fresh()->elective_slot_id)->toBeNull();
});

test('the same elective cannot fill two slots', function () {
    [$student, , $slot, $electives] = makeStudentWithElectives();

    $this->actingAs($student)->get(route('student.study-plan'))->assertOk();

    $first = electiveSlotEntry($student, $slot);
    $second = StudyPlanEntry::where('user_id', $student->id)
        ->whereHas('course', fn ($query) => $query->where('course_name_en', 'Elektif II'))
        ->firstOrFail();

    $this->actingAs($student)
        ->put(route('student.study-plan.elective', $first), ['course_id' => $electives[0]->id])
        ->assertSessionHasNoErrors();

    // The second slot refuses the course the first one already uses.
    $this->actingAs($student)
        ->put(route('student.study-plan.elective', $second), ['course_id' => $electives[0]->id])
        ->assertSessionHasErrors('course_id');

    expect($second->fresh()->course_id)->toBe($second->course_id)
        ->and($second->fresh()->elective_slot_id)->toBeNull();

    // Another course is accepted.
    $this->actingAs($student)
        ->put(route('student.study-plan.elective', $second), ['course_id' => $electives[1]->id])
        ->assertSessionHasNoErrors();

    expect($second->fresh()->elective_slot_id)->toBe($second->course_id);
});

test('an unfilled choice can be cleared back to the bare slot', function () {
    [$student, , $slot, $electives] = makeStudentWithElectives();

    $entry = electiveSlotEntry($student, $slot);

    $this->actingAs($student)
        ->put(route('student.study-plan.elective', $entry), ['course_id' => $electives[0]->id])
        ->assertSessionHasNoErrors();

    $this->actingAs($student)
        ->put(route('student.study-plan.elective', $entry), ['course_id' => ''])
        ->assertSessionHasNoErrors();

    $entry->refresh();

    expect($entry->course_id)->toBe($slot->id)
        ->and($entry->elective_slot_id)->toBeNull()
        ->and($entry->isElectiveOption())->toBeTrue()
        ->and($entry->hasElectiveChoice())->toBeFalse();
});

test('an elective slot is part of the curriculum and cannot be removed or moved', function () {
    [$student, , $slot, $electives] = makeStudentWithElectives();

    $this->actingAs($student)->get(route('student.study-plan'))->assertOk();

    $entry = electiveSlotEntry($student, $slot);

    $this->actingAs($student)
        ->delete(route('student.study-plan.remove', $entry))
        ->assertSessionHasErrors('entry');

    $this->actingAs($student)
        ->put(route('student.study-plan.term', $entry), ['term' => '4:2'])
        ->assertSessionHasErrors('term');

    expect(StudyPlanEntry::whereKey($entry->id)->exists())->toBeTrue()
        ->and($entry->fresh()->only(['year', 'semester']))->toBe(['year' => 2, 'semester' => 2]);

    // Filling it in does not turn it into a movable, removable course either.
    $this->actingAs($student)
        ->put(route('student.study-plan.elective', $entry), ['course_id' => $electives[0]->id])
        ->assertSessionHasNoErrors();

    $this->actingAs($student)
        ->delete(route('student.study-plan.remove', $entry->fresh()))
        ->assertSessionHasErrors('entry');

    expect($entry->fresh()->course_id)->toBe($electives[0]->id);
});

test('an advisor sees which elective option a chosen elective course replaced', function () {
    [$student, , $slot, $electives] = makeStudentWithElectives();

    $advisor = User::factory()->create(['role' => 'advisor', 'staff_id' => 'adv-elective']);
    $student->forceFill(['advisor_id' => $advisor->staff_id, 'plan_status' => 'submitted'])->save();

    $entry = electiveSlotEntry($student, $slot);

    $this->actingAs($student)
        ->put(route('student.study-plan.elective', $entry), ['course_id' => $electives[0]->id])
        ->assertSessionHasNoErrors();

    // My Supervisees: the chosen "Elective Course" keeps the name of the
    // "Elective Option" it replaced, so the curriculum slot stays traceable.
    $this->actingAs($advisor)
        ->get(route('advisor.dashboard', ['student' => $student->id, 'tab' => 'plan']))
        ->assertOk()
        ->assertSee($electives[0]->code.' · '.$electives[0]->name)
        ->assertSee('Previously: Elektif I');
});

test('a plan without elective courses shows no elective panel', function () {
    [$student] = makeStudentWithCurriculum();

    $this->actingAs($student)
        ->get(route('student.study-plan'))
        ->assertOk()
        ->assertDontSee('Elective Course')
        ->assertDontSee('Elective Option');
});

test('a plan row that is not an elective option refuses an elective choice', function () {
    [$student, , , $electives] = makeStudentWithElectives();

    $this->actingAs($student)->get(route('student.study-plan'))->assertOk();

    $core = StudyPlanEntry::where('user_id', $student->id)
        ->whereHas('course', fn ($query) => $query->where('course_name_en', 'Core Course'))
        ->firstOrFail();

    $this->actingAs($student)
        ->put(route('student.study-plan.elective', $core), ['course_id' => $electives[0]->id])
        ->assertSessionHasErrors('course_id');

    expect($core->fresh()->course_id)->not->toBe($electives[0]->id);
});
