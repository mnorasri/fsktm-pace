<?php

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\Department;
use App\Models\PlanCourse;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

/**
 * A Head of Department with a department, a programme and one study plan.
 *
 * @return array{0: User, 1: Department, 2: Program, 3: CoursePlan}
 */
function makeHodWithPlan(): array
{
    $department = Department::create(['code' => 'JKP', 'name' => 'Software Engineering']);
    $program = Program::create([
        'department_id' => $department->id,
        'code' => 'BIK',
        'name' => 'Bachelor of Software Engineering',
        'duration_years' => 3.5,
    ]);
    $plan = CoursePlan::create([
        'program_id' => $program->id,
        'code' => 'BIK2025-LOCAL',
        'name' => 'BIK Local Students',
        'version' => '2025',
    ]);
    $hod = User::factory()->create([
        'role' => 'advisor',
        'is_hod' => true,
        'department_id' => $department->id,
    ]);

    return [$hod, $department, $program, $plan];
}

function makePlanCourse(string $code, int $semester = 1, ?int $planId = null, array $attributes = []): Course
{
    return Course::create(array_merge([
        'code' => $code,
        'name' => $code.' course',
        'credits' => 3,
        'semester' => $semester,
        'course_plan_id' => $planId,
    ], $attributes));
}

test('a head of department can add a course to the directory', function () {
    [$hod, $department] = makeHodWithPlan();

    // The shared course page renders against the HoD routes, without import.
    $this->actingAs($hod)
        ->get(route('advisor.courses.index'))
        ->assertOk()
        ->assertSee('Manage Courses')
        ->assertSee('New course')
        ->assertDontSee('Import CSV');

    $this->actingAs($hod)
        ->post(route('advisor.courses.store'), [
            'course_code' => 'BIK 10003',
            'course_name_en' => 'Programming Technique',
            'credit_hours' => 3,
            'semester' => 1,
        ])
        ->assertRedirect();

    // The course belongs to the department the HoD heads.
    $this->assertDatabaseHas('courses', [
        'course_code' => 'BIK 10003',
        'department_id' => $department->id,
    ]);
});

test('a head of department can edit and delete a course', function () {
    [$hod, , , $plan] = makeHodWithPlan();
    $course = makePlanCourse('BIK 10103', 1, $plan->id);

    $this->actingAs($hod)
        ->put(route('advisor.courses.update', $course), [
            'course_code' => 'BIK 10103',
            'course_name_en' => 'Programming Technique I',
            'credit_hours' => 4,
            'semester' => 2,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'course_name_en' => 'Programming Technique I',
        'credit_hours' => 4,
        'semester' => 2,
    ]);

    $this->actingAs($hod)
        ->delete(route('advisor.courses.destroy', $course))
        ->assertRedirect();

    $this->assertDatabaseMissing('courses', ['id' => $course->id]);
});

test('the course page is only open to heads of department', function () {
    $advisor = User::factory()->create(['role' => 'advisor']);

    $this->actingAs($advisor)->get(route('advisor.courses.index'))->assertForbidden();
    $this->actingAs($advisor)->get(route('advisor.plans.index'))->assertForbidden();
    $this->actingAs($advisor)
        ->post(route('advisor.plans.store'), ['code' => 'X', 'name' => 'X'])
        ->assertForbidden();
});

test('an administrator can open the head of department tools', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('advisor.courses.index'))
        ->assertOk()
        ->assertSee('Manage Courses');

    $this->actingAs($admin)
        ->get(route('advisor.plans.index'))
        ->assertOk()
        ->assertSee('Study Plan');
});

test('the study plan page shows the curriculum year by year', function () {
    [$hod, , , $plan] = makeHodWithPlan();

    // Odd curriculum semester 3 sits in Year 2 Semester 1.
    makePlanCourse('BIK 20103', 3, $plan->id);
    // Even semester 4 sits in Year 2 Semester 2.
    makePlanCourse('BIK 20203', 4, $plan->id);

    // The elective catalogue has no term of its own.
    $electiveType = CourseType::create(['code' => CourseType::ELECTIVE_COURSE, 'name' => 'Elective Courses']);
    makePlanCourse('BIK 29903', 99, $plan->id, ['course_type_code' => $electiveType->code]);

    $this->actingAs($hod)
        ->get(route('advisor.plans.index', ['plan_id' => $plan->id]))
        ->assertOk()
        ->assertSee('BIK2025-LOCAL')
        ->assertSee('BIK 20103')
        ->assertSee('BIK 20203')
        ->assertSee('Year 2')
        ->assertSee('Semester 1')
        ->assertSee('Semester 2')
        ->assertSee('Courses without a term')
        ->assertSee('BIK 29903');
});

test('a head of department can create a new study plan', function () {
    [$hod, , $program] = makeHodWithPlan();

    $response = $this->actingAs($hod)->post(route('advisor.plans.store'), [
        'program_id' => $program->id,
        'code' => 'BIK2026-LOCAL',
        'name' => 'BIK Local Students — 2026',
        'version' => '2026',
        'total_credits' => 120,
    ]);

    $plan = CoursePlan::where('code', 'BIK2026-LOCAL')->first();

    expect($plan)->not->toBeNull()
        ->and($plan->program_id)->toBe($program->id);

    $response->assertRedirect(route('advisor.plans.index', ['plan_id' => $plan->id]));
});

test('a plan code must be unique', function () {
    [$hod, , $program, $plan] = makeHodWithPlan();

    $this->actingAs($hod)
        ->post(route('advisor.plans.store'), [
            'program_id' => $program->id,
            'code' => $plan->code,
            'name' => 'Another plan',
        ])
        ->assertSessionHasErrors('code');
});

test('a head of department can add an existing course to a plan', function () {
    [$hod, , , $plan] = makeHodWithPlan();
    $course = makePlanCourse('BIK 10203');

    $this->actingAs($hod)
        ->post(route('advisor.plans.courses.store', $plan), [
            'course_id' => $course->id,
            'year' => 2,
            'semester' => 1,
            'category' => 'required',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('plan_courses', [
        'course_plan_id' => $plan->id,
        'course_id' => $course->id,
        'year' => 2,
        'semester' => 1,
        'category' => 'required',
        'credits' => 3,
    ]);

    // The same course cannot fill two rows of one plan.
    $this->actingAs($hod)
        ->post(route('advisor.plans.courses.store', $plan), [
            'course_id' => $course->id,
            'year' => 3,
            'semester' => 2,
        ])
        ->assertSessionHasErrors('course_id');

    expect($plan->planCourses()->where('course_id', $course->id)->count())->toBe(1);
});

test('a placed course can be moved to another term and removed', function () {
    [$hod, , , $plan] = makeHodWithPlan();
    $course = makePlanCourse('BIK 10303');
    $slot = PlanCourse::create([
        'course_plan_id' => $plan->id,
        'course_id' => $course->id,
        'year' => 1,
        'semester' => 1,
        'category' => 'required',
        'credits' => 3,
    ]);

    $this->actingAs($hod)
        ->put(route('advisor.plans.courses.update', [$plan, $slot]), [
            'year' => 3,
            'semester' => 2,
            'category' => 'elective',
            'credits' => 4,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('plan_courses', [
        'id' => $slot->id,
        'year' => 3,
        'semester' => 2,
        'category' => 'elective',
        'credits' => 4,
    ]);

    $this->actingAs($hod)
        ->delete(route('advisor.plans.courses.destroy', [$plan, $slot]))
        ->assertRedirect();

    $this->assertDatabaseMissing('plan_courses', ['id' => $slot->id]);
});

test('an attached course can be pinned to a term and detached from the plan', function () {
    [$hod, , , $plan] = makeHodWithPlan();
    $course = makePlanCourse('BIK 10403', 3, $plan->id);

    // Pinning adds the explicit row; the course stays attached to the plan.
    $this->actingAs($hod)
        ->post(route('advisor.plans.courses.store', $plan), [
            'course_id' => $course->id,
            'year' => 2,
            'semester' => 2,
            'category' => 'required',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('plan_courses', [
        'course_plan_id' => $plan->id,
        'course_id' => $course->id,
        'year' => 2,
        'semester' => 2,
    ]);

    // Detaching takes it off the plan but leaves the course itself alone.
    $this->actingAs($hod)
        ->delete(route('advisor.plans.attached.destroy', [$plan, $course]))
        ->assertRedirect();

    $this->assertDatabaseHas('courses', ['id' => $course->id, 'course_plan_id' => null]);
});

test('a course attached to another plan cannot be detached from this one', function () {
    [$hod, , $program, $plan] = makeHodWithPlan();

    $otherPlan = CoursePlan::create([
        'program_id' => $program->id,
        'code' => 'BIK2025-INT',
        'name' => 'BIK International Students',
    ]);

    $course = makePlanCourse('BIK 10503', 1, $otherPlan->id);

    $this->actingAs($hod)
        ->delete(route('advisor.plans.attached.destroy', [$plan, $course]))
        ->assertNotFound();

    $this->assertDatabaseHas('courses', ['id' => $course->id, 'course_plan_id' => $otherPlan->id]);

    // A slot that belongs to another plan cannot be edited through this plan.
    $slot = PlanCourse::create([
        'course_plan_id' => $otherPlan->id,
        'course_id' => $course->id,
        'year' => 1,
        'semester' => 1,
        'credits' => 3,
    ]);

    $this->actingAs($hod)
        ->put(route('advisor.plans.courses.update', [$plan, $slot]), [
            'year' => 2,
            'semester' => 1,
            'category' => 'required',
            'credits' => 3,
        ])
        ->assertNotFound();
});

test('a head of department can bulk import courses into a plan from a CSV', function () {
    [$hod, , , $plan] = makeHodWithPlan();
    makePlanCourse('BIK 10103');
    makePlanCourse('BIK 10203');

    $csv = implode("\n", [
        'course_code,year,semester',
        'BIK 10103,1,1',
        'BIK 10203,2,2',
    ])."\n";

    $this->actingAs($hod)
        ->post(route('advisor.plans.courses.import', $plan), [
            'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
        ])
        ->assertSessionHasNoErrors();

    $report = session('import_report');

    expect($report['created'])->toBe(2)
        ->and($report['failed'])->toBe(0)
        ->and($plan->planCourses()->count())->toBe(2);

    $this->assertDatabaseHas('plan_courses', [
        'course_plan_id' => $plan->id,
        'year' => 1,
        'semester' => 1,
    ])->assertDatabaseHas('plan_courses', [
        'course_plan_id' => $plan->id,
        'year' => 2,
        'semester' => 2,
    ]);
});

test('a plan course import skips rows with an unknown course or a bad term', function () {
    [$hod, , , $plan] = makeHodWithPlan();
    makePlanCourse('BIK 10103');

    $csv = implode("\n", [
        'course_code,year,semester',
        'BIK 10103,1,1',
        'NOPE 00001,1,1',
        'BIK 10103,9,1',
        'BIK 10103,1,4',
    ])."\n";

    $this->actingAs($hod)
        ->post(route('advisor.plans.courses.import', $plan), [
            'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
        ])
        ->assertSessionHasNoErrors();

    $report = session('import_report');

    expect($report['created'])->toBe(1)
        ->and($report['failed'])->toBe(3)
        ->and(implode(' | ', $report['failures']))
        ->toContain('NOPE 00001 does not exist')
        ->toContain('year must be a whole number between 1 and 8')
        ->toContain('semester must be 1, 2 or 3');

    expect($plan->planCourses()->count())->toBe(1);
});

test('a plan course import moves a course already on the plan instead of duplicating it', function () {
    [$hod, , , $plan] = makeHodWithPlan();
    $course = makePlanCourse('BIK 10103');
    PlanCourse::create([
        'course_plan_id' => $plan->id,
        'course_id' => $course->id,
        'year' => 1,
        'semester' => 1,
        'category' => 'required',
        'credits' => 3,
    ]);

    $csv = "course_code,year,semester\nBIK 10103,3,2\n";

    $this->actingAs($hod)
        ->post(route('advisor.plans.courses.import', $plan), [
            'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
        ])
        ->assertSessionHasNoErrors();

    $report = session('import_report');

    expect($report['updated'])->toBe(1)
        ->and($plan->planCourses()->count())->toBe(1);

    $this->assertDatabaseHas('plan_courses', [
        'course_plan_id' => $plan->id,
        'course_id' => $course->id,
        'year' => 3,
        'semester' => 2,
    ]);
});

test('the same course may sit on several study plans at once', function () {
    [$hod, , $program, $plan] = makeHodWithPlan();
    $other = CoursePlan::create([
        'program_id' => $program->id,
        'code' => 'BIK2025-INT',
        'name' => 'BIK International Students',
    ]);
    $course = makePlanCourse('BIK 10103');

    $csv = "course_code,year,semester\nBIK 10103,2,1\n";

    $this->actingAs($hod)
        ->post(route('advisor.plans.courses.import', $plan), [
            'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($hod)
        ->post(route('advisor.plans.courses.import', $other), [
            'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
        ])
        ->assertSessionHasNoErrors();

    expect($plan->planCourses()->where('course_id', $course->id)->count())->toBe(1)
        ->and($other->planCourses()->where('course_id', $course->id)->count())->toBe(1);
});
