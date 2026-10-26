<?php

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\Department;
use App\Models\MqfSc;
use App\Models\PlanCourse;
use App\Models\Program;
use App\Models\Sdg;
use App\Models\User;
use App\Models\Vbe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

/**
 * A department, programme, course type and the three curriculum managers.
 *
 * @return array{department: Department, program: Program, admin: User, hod: User, hop: User, advisor: User, student: User}
 */
function curriculumContext(): array
{
    $department = Department::create(['code' => 'JKP', 'name' => 'Software Engineering']);

    $program = Program::create([
        'department_id' => $department->id,
        'code' => 'BIK',
        'name' => 'Bachelor of Software Engineering',
        'duration_years' => 3.5,
        'total_credits' => 130,
    ]);

    CourseType::create(['code' => 'CT02', 'name' => 'Core Course']);

    $admin = User::factory()->create(['role' => 'admin']);
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true, 'department_id' => $department->id]);
    $hop = User::factory()->create(['role' => 'advisor', 'department_id' => $department->id]);
    $program->update(['head_of_program_id' => $hop->id]);

    return [
        'department' => $department,
        'program' => $program,
        'admin' => $admin,
        'hod' => $hod,
        'hop' => $hop,
        'advisor' => User::factory()->create(['role' => 'advisor']),
        'student' => User::factory()->create(['role' => 'student']),
    ];
}

function importCurriculumCsv(string $route, string $csv): void
{
    $admin = User::where('role', 'admin')->first()
        ?? User::factory()->create(['role' => 'admin']);

    test()->actingAs($admin)->post(route($route), [
        'file' => UploadedFile::fake()->createWithContent('import.csv', $csv),
    ])->assertSessionHasNoErrors();
}

test('the four curriculum pages are open to the administrator, HoD and HoP', function () {
    $context = curriculumContext();

    $routes = [
        'curriculum.programmes.index',
        'curriculum.courses.index',
        'curriculum.study-plans.index',
        'curriculum.study-plan-details.index',
    ];

    foreach (['admin', 'hod', 'hop'] as $manager) {
        foreach ($routes as $route) {
            $this->actingAs($context[$manager])
                ->get(route($route))
                ->assertOk();
        }
    }

    foreach (['advisor', 'student'] as $outsider) {
        foreach ($routes as $route) {
            $this->actingAs($context[$outsider])
                ->get(route($route))
                ->assertForbidden();
        }
    }
});

test('a programme can be created, updated and deleted', function () {
    $context = curriculumContext();

    $this->actingAs($context['hod'])
        ->post(route('curriculum.programmes.store'), [
            'programme_code' => 'BIT',
            'programme_name' => 'Bachelor of Information Technology',
            'department_code' => 'JKP',
            'duration_years' => 4,
            'total_credits' => 132,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('programs', [
        'code' => 'BIT',
        'name' => 'Bachelor of Information Technology',
        'department_id' => $context['department']->id,
        'duration_years' => 4,
        'total_credits' => 132,
    ]);

    $program = Program::where('code', 'BIT')->firstOrFail();

    $this->actingAs($context['hop'])
        ->put(route('curriculum.programmes.update', $program), [
            'programme_code' => 'BIT',
            'programme_name' => 'Information Technology',
            'department_code' => 'JKP',
            'duration_years' => 3.5,
            'total_credits' => 130,
        ])
        ->assertRedirect();

    $program->refresh();

    expect($program->name)->toBe('Information Technology')
        ->and($program->duration_years)->toBe(3.5);

    $this->actingAs($context['admin'])
        ->delete(route('curriculum.programmes.destroy', $program))
        ->assertRedirect();

    $this->assertDatabaseMissing('programs', ['id' => $program->id]);
});

test('the programme import creates valid rows and skips unknown departments', function () {
    $context = curriculumContext();

    importCurriculumCsv('curriculum.programmes.import', implode("\n", [
        'programme_code,programme_name,department_code',
        'BIT,Bachelor of Information Technology,JKP',
        'BIM,Bachelor of Information Multimedia,JKP',
        'BAD,Bachelor of Unknown,NOPE',
    ])."\n");

    $report = session('import_report');

    expect($report['created'])->toBe(2)
        ->and($report['failed'])->toBe(1)
        ->and($report['failures'][0])->toContain('NOPE');

    $this->assertDatabaseHas('programs', ['code' => 'BIT', 'department_id' => $context['department']->id]);
    $this->assertDatabaseMissing('programs', ['code' => 'BAD']);
});

test('a course can be created, updated and deleted using reference codes', function () {
    $context = curriculumContext();
    MqfSc::create(['code' => 'SC1', 'name' => 'Knowledge']);
    Vbe::create(['code' => 'VBE1', 'name' => 'Entrepreneurship']);
    $sdg = Sdg::where('code', 'SDG4')->firstOrFail();

    $this->actingAs($context['hod'])
        ->post(route('curriculum.courses.store'), [
            'course_code' => 'BIK 10103',
            'course_name_en' => 'Programming Technique I',
            'course_name_my' => 'Teknik Pengaturcaraan I',
            'credit_hours' => 3,
            'course_type_code' => 'CT02',
            'course_load' => 3,
            'pre_requisite' => 'BIK 10003',
            'sc_code' => 'SC1',
            'vbe_ept_code' => 'VBE1',
            'sdg_code' => 'SDG4',
        ])
        ->assertRedirect();

    $course = Course::where('course_code', 'BIK 10103')->firstOrFail();

    expect($course->name)->toBe('Programming Technique I')
        ->and($course->course_name_my)->toBe('Teknik Pengaturcaraan I')
        ->and((int) $course->credits)->toBe(3)
        ->and($course->prerequisites)->toBe('BIK 10003')
        ->and($course->courseType?->code)->toBe('CT02')
        ->and($course->mqfSc?->code)->toBe('SC1')
        ->and($course->vbe?->code)->toBe('VBE1')
        ->and($course->sdg?->code)->toBe($sdg->code)
        ->and((int) $course->department_id)->toBe((int) $context['department']->id);

    $this->actingAs($context['admin'])
        ->put(route('curriculum.courses.update', $course), [
            'course_code' => 'BIK 10103',
            'course_name_en' => 'Programming Technique II',
            'course_name_my' => '',
            'credit_hours' => 4,
            'course_type_code' => '',
            'course_load' => 4,
            'pre_requisite' => '',
            'sc_code' => '',
            'vbe_ept_code' => '',
            'sdg_code' => '',
        ])
        ->assertRedirect();

    $course->refresh();

    expect($course->name)->toBe('Programming Technique II')
        ->and($course->credits)->toBe(4)
        ->and($course->course_type_code)->toBeNull()
        ->and($course->prerequisites)->toBeNull();

    $this->actingAs($context['admin'])
        ->delete(route('curriculum.courses.destroy', $course))
        ->assertRedirect();

    $this->assertDatabaseMissing('courses', ['id' => $course->id]);
});

test('the course import resolves codes and skips unknown references', function () {
    curriculumContext();
    CourseType::create(['code' => 'CT01', 'name' => 'University Course']);

    importCurriculumCsv('curriculum.courses.import', implode("\n", [
        'course_code,course_name_en,course_name_my,credit_hours,course_type_code,course_load,pre_requisite,sc_code,vbe_ept_code,sdg_code',
        'BIK 10202,Discrete Mathematics,Matematik Diskret,2,CT02,2,,,,',
        'BIK 10303,Engineering Ethics,Etika Kejuruteraan,3,CT01,3,,,,SDG9',
        'BIK 10404,Broken Row,Broken,3,CT02,3,,,,SDG99',
    ])."\n");

    $report = session('import_report');

    expect($report['created'])->toBe(2)
        ->and($report['failed'])->toBe(1)
        ->and($report['failures'][0])->toContain('SDG99');

    $this->assertDatabaseHas('courses', [
        'course_code' => 'BIK 10202',
        'course_name_my' => 'Matematik Diskret',
        'credit_hours' => 2,
    ]);
    $this->assertDatabaseMissing('courses', ['course_code' => 'BIK 10404']);
});

test('a study plan can be created, updated and deleted', function () {
    $context = curriculumContext();

    $this->actingAs($context['hod'])
        ->post(route('curriculum.study-plans.store'), [
            'study_plan_code' => 'BIK2025-LOCAL',
            'study_plan_name' => 'BIK Local Students',
            'programme_code' => 'BIK',
            'version' => '2025',
            'total_semester' => 8,
        ])
        ->assertRedirect();

    $plan = CoursePlan::where('code', 'BIK2025-LOCAL')->firstOrFail();

    expect($plan->name)->toBe('BIK Local Students')
        ->and($plan->version)->toBe('2025')
        ->and((int) $plan->total_semester)->toBe(8)
        ->and((int) $plan->program_id)->toBe((int) $context['program']->id);

    $this->actingAs($context['hop'])
        ->put(route('curriculum.study-plans.update', $plan), [
            'study_plan_code' => 'BIK2025-LOCAL',
            'study_plan_name' => 'BIK Local Students (Revised)',
            'programme_code' => 'BIK',
            'version' => '2026',
            'total_semester' => 7,
        ])
        ->assertRedirect();

    $plan->refresh();

    expect($plan->name)->toBe('BIK Local Students (Revised)')
        ->and($plan->version)->toBe('2026')
        ->and($plan->total_semester)->toBe(7);

    $this->actingAs($context['admin'])
        ->delete(route('curriculum.study-plans.destroy', $plan))
        ->assertRedirect();

    $this->assertDatabaseMissing('course_plans', ['id' => $plan->id]);
});

test('the study plan import creates valid rows and reports unknown programmes', function () {
    curriculumContext();

    importCurriculumCsv('curriculum.study-plans.import', implode("\n", [
        'study_plan_code,study_plan_name,programme_code,version,total_semester',
        'BIK2025-LOCAL,Local Students,BIK,2025,8',
        'BIK2025-INT,International Students,BIK,2025,8',
        'BAD2025-LOCAL,No Programme,NOPE,2025,8',
    ])."\n");

    $report = session('import_report');

    expect($report['created'])->toBe(2)
        ->and($report['failed'])->toBe(1);

    $this->assertDatabaseHas('course_plans', [
        'code' => 'BIK2025-LOCAL',
        'total_semester' => 8,
    ]);
    $this->assertDatabaseMissing('course_plans', ['code' => 'BAD2025-LOCAL']);
});

test('study plan details map a curriculum semester to a year and term', function () {
    $context = curriculumContext();
    $plan = CoursePlan::create([
        'program_id' => $context['program']->id,
        'code' => 'BIK2025-LOCAL',
        'name' => 'Local',
        'total_semester' => 8,
    ]);
    $course = Course::create(['code' => 'BIK 10503', 'name' => 'Software Design', 'credits' => 3]);

    $this->actingAs($context['hod'])
        ->post(route('curriculum.study-plan-details.store'), [
            'study_plan_code' => 'BIK2025-LOCAL',
            'course_code' => 'BIK 10503',
            'semester' => 5,
        ])
        ->assertRedirect();

    $detail = PlanCourse::where('course_plan_id', $plan->id)
        ->where('course_id', $course->id)
        ->firstOrFail();

    expect($detail->year)->toBe(3)
        ->and($detail->semester)->toBe(1)
        ->and($detail->curriculumSemester())->toBe(5)
        ->and($detail->category)->toBe('required');

    $this->actingAs($context['hop'])
        ->put(route('curriculum.study-plan-details.update', $detail), [
            'study_plan_code' => 'BIK2025-LOCAL',
            'course_code' => 'BIK 10503',
            'semester' => 6,
        ])
        ->assertRedirect();

    $detail->refresh();

    expect($detail->year)->toBe(3)
        ->and($detail->semester)->toBe(2)
        ->and($detail->curriculumSemester())->toBe(6);

    // The same course cannot appear twice in one plan.
    $this->actingAs($context['admin'])
        ->post(route('curriculum.study-plan-details.store'), [
            'study_plan_code' => 'BIK2025-LOCAL',
            'course_code' => 'BIK 10503',
            'semester' => 7,
        ])
        ->assertSessionHasErrors('course_code');

    // Nor beyond the plan's own semester count.
    $this->actingAs($context['admin'])
        ->post(route('curriculum.study-plan-details.store'), [
            'study_plan_code' => 'BIK2025-LOCAL',
            'course_code' => 'BIK 10503',
            'semester' => 9,
        ])
        ->assertSessionHasErrors('semester');

    $this->actingAs($context['admin'])
        ->delete(route('curriculum.study-plan-details.destroy', $detail))
        ->assertRedirect();

    $this->assertDatabaseMissing('plan_courses', ['id' => $detail->id]);
});

test('the study plan detail import upserts rows and skips unknown references', function () {
    $context = curriculumContext();
    CoursePlan::create([
        'program_id' => $context['program']->id,
        'code' => 'BIK2025-LOCAL',
        'name' => 'Local',
        'total_semester' => 8,
    ]);
    Course::create(['code' => 'BIK 10603', 'name' => 'Database', 'credits' => 3]);
    Course::create(['code' => 'BIK 10703', 'name' => 'Networks', 'credits' => 3]);

    importCurriculumCsv('curriculum.study-plan-details.import', implode("\n", [
        'study_plan_code,course_code,semester',
        'BIK2025-LOCAL,BIK 10603,1',
        'BIK2025-LOCAL,BIK 10703,2',
        'BIK2025-LOCAL,NOPE 101,3',
        'NOPE-PLAN,BIK 10603,4',
    ])."\n");

    $report = session('import_report');

    expect($report['created'])->toBe(2)
        ->and($report['failed'])->toBe(2);

    $this->assertDatabaseHas('plan_courses', ['year' => 1, 'semester' => 2]);

    // Importing the same pair again updates the semester rather than duplicating.
    importCurriculumCsv('curriculum.study-plan-details.import', implode("\n", [
        'study_plan_code,course_code,semester',
        'BIK2025-LOCAL,BIK 10603,3',
    ])."\n");

    $report = session('import_report');

    expect($report['created'])->toBe(0)
        ->and($report['updated'])->toBe(1);
});
