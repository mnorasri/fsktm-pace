<?php

use App\Models\CoursePlan;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * Two programmes with plans, plus one advisor, so an import row can name a
 * programme, a plan, both, or neither.
 *
 * @return array{0: Program, 1: CoursePlan, 2: Program, 3: CoursePlan, 4: User}
 */
function importPlacementFixture(): array
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
        'role' => 'advisor', 'staff_id' => 'norasri',
        'email' => 'norasri@uthm.edu.my',
    ]);

    return [$cs, $csPlan, $is, $isPlan, $advisor];
}

/**
 * Upload a student CSV as an administrator and return the import report.
 *
 * @return array<string, mixed>
 */
function importStudentsCsv(string $csv): array
{
    $admin = User::factory()->create(['role' => 'admin']);

    test()->actingAs($admin)->post(route('admin.students.import'), [
        'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
    ])->assertSessionHasNoErrors();

    return session('import_report');
}

test('a student CSV without a plan_code column still imports', function () {
    [$cs] = importPlacementFixture();

    $csv = implode("\n", [
        'matric_num,student_name,student_email,program_code,advisor_id,password',
        'A220101,No Plan Student,noplan@student.fsktm.test,CS,norasri,secret123',
    ])."\n";

    $report = importStudentsCsv($csv);

    expect($report['created'])->toBe(1)
        ->and($report['failed'])->toBe(0);

    $student = User::where('student_id', 'A220101')->firstOrFail();

    // The omitted plan column must not borrow the advisor or password cell.
    expect($student->program_id)->toBe($cs->id)
        ->and($student->plan_id)->toBeNull()
        ->and($student->advisor_id)->toBe('norasri')
        ->and($student->department_id)->toBe($cs->department_id)
        ->and(Hash::check('secret123', $student->password))->toBeTrue();
});

test('a student CSV with a plan_code stores that plan', function () {
    [$cs, $csPlan] = importPlacementFixture();

    $csv = implode("\n", [
        'matric_num,student_name,student_email,program_code,plan_code,advisor_id,password',
        'A220102,Plan Student,plan@student.fsktm.test,CS,CS241,norasri,',
    ])."\n";

    importStudentsCsv($csv);

    $student = User::where('student_id', 'A220102')->firstOrFail();

    expect($student->program_id)->toBe($cs->id)
        ->and($student->plan_id)->toBe($csPlan->id);
});

test('a plan from another programme is skipped with a clear reason', function () {
    importPlacementFixture();

    $csv = implode("\n", [
        'matric_num,student_name,student_email,program_code,plan_code,advisor_id,password',
        'A220103,Mismatch Student,mismatch@student.fsktm.test,CS,IS223,,',
    ])."\n";

    $report = importStudentsCsv($csv);

    expect($report['created'])->toBe(0)
        ->and($report['failed'])->toBe(1)
        ->and($report['failures'][0])->toContain('does not belong to program_code');

    expect(User::where('student_id', 'A220103')->exists())->toBeFalse();
});

test('a plan_code on its own adopts the plan programme', function () {
    [, , $is, $isPlan] = importPlacementFixture();

    $csv = implode("\n", [
        'matric_num,student_name,student_email,plan_code,advisor_id,password',
        'A220104,Plan Only,planonly@student.fsktm.test,IS223,norasri,',
    ])."\n";

    $report = importStudentsCsv($csv);

    expect($report['failed'])->toBe(0);

    $student = User::where('student_id', 'A220104')->firstOrFail();

    expect($student->program_id)->toBe($is->id)
        ->and($student->plan_id)->toBe($isPlan->id)
        ->and($student->department_id)->toBe($is->department_id);
});

test('re-importing without a plan_code keeps the assigned plan and advisor', function () {
    [$cs, $csPlan] = importPlacementFixture();

    importStudentsCsv("matric_num,student_name,student_email,program_code,plan_code,advisor_id,password\n"
        ."A220105,Keep Plan,keep@student.fsktm.test,CS,CS241,norasri,\n");

    // A roster sheet that carries neither plan nor advisor.
    importStudentsCsv("matric_num,student_name,student_email,program_code\n"
        ."A220105,Keep Plan Renamed,keep@student.fsktm.test,CS\n");

    $student = User::where('student_id', 'A220105')->firstOrFail();

    expect($student->name)->toBe('Keep Plan Renamed')
        ->and($student->program_id)->toBe($cs->id)
        ->and($student->plan_id)->toBe($csPlan->id)
        ->and($student->advisor_id)->toBe('norasri');
});

test('moving a student to another programme through import clears the old plan', function () {
    [, , $is] = importPlacementFixture();

    importStudentsCsv("matric_num,student_name,student_email,program_code,plan_code,advisor_id,password\n"
        ."A220106,Moving Student,moving@student.fsktm.test,CS,CS241,norasri,\n");

    importStudentsCsv("matric_num,student_name,student_email,program_code\n"
        ."A220106,Moving Student,moving@student.fsktm.test,IS\n");

    $student = User::where('student_id', 'A220106')->firstOrFail();

    expect($student->program_id)->toBe($is->id)
        ->and($student->plan_id)->toBeNull()
        ->and($student->department_id)->toBe($is->department_id);
});
