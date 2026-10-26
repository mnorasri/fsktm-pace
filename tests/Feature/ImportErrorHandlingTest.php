<?php

use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

/**
 * Upload a CSV as the given administrator.
 */
function importCsv(User $admin, string $route, string $csv)
{
    return test()->actingAs($admin)->post(route($route), [
        'file' => UploadedFile::fake()->createWithContent('import.csv', $csv),
    ]);
}

test('a student import skips bad rows and keeps importing the rest', function () {
    $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@fsktm.test']);
    User::factory()->create(['role' => 'advisor', 'staff_id' => 'ezak', 'email' => 'ezak@uthm.edu.my']);

    $csv = implode("\n", [
        'matric_num,student_name,student_email,program_code,plan_code,advisor_id,password',
        'A210001,Good Row,good@student.fsktm.test,,,ezak,secret123',
        ',No Matric,no-matric@student.fsktm.test,,,ezak,',
        'A210003,Unknown Advisor,unknown@student.fsktm.test,,,nobody,',
        'A210004,Clashing Email,admin@fsktm.test,,,ezak,',
        'A210005,Late Good Row,late@student.fsktm.test,,,ezak,',
    ])."\n";

    importCsv($admin, 'admin.students.import', $csv)->assertSessionHasNoErrors();

    $report = session('import_report');

    expect($report['created'])->toBe(2)
        ->and($report['updated'])->toBe(0)
        ->and($report['failed'])->toBe(3)
        ->and(implode(' | ', $report['failures']))
        ->toContain('Line 3: matric_num is required')
        ->toContain('Line 4: advisor_id "nobody"')
        ->toContain('Line 5: email "admin@fsktm.test" belongs to an existing admin account');

    // The good rows on either side of the failures still landed.
    expect(User::where('student_id', 'A210001')->exists())->toBeTrue()
        ->and(User::where('student_id', 'A210005')->exists())->toBeTrue()
        ->and(User::where('student_id', 'A210003')->exists())->toBeFalse()
        ->and(User::where('student_id', 'A210004')->exists())->toBeFalse();

    // A clashing email must never convert an existing account into a student.
    expect(User::where('email', 'admin@fsktm.test')->value('role'))->toBe('admin');
});

test('a lecturer import skips rows with a missing staff id or unknown department', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Department::create(['code' => 'JKP', 'name' => 'Software Engineering']);

    $csv = implode("\n", [
        'staff_id,lecturer_name,lecturer_email,department_code,password',
        'LEC100,Valid Lecturer,valid@uthm.edu.my,JKP,secret123',
        ',No Staff Id,no-staff@uthm.edu.my,JKP,',
        'LEC200,Unknown Department,unknown@uthm.edu.my,NOPE,',
    ])."\n";

    importCsv($admin, 'admin.lecturers.import', $csv)->assertSessionHasNoErrors();

    $report = session('import_report');

    expect($report['created'])->toBe(1)
        ->and($report['failed'])->toBe(2)
        ->and(implode(' | ', $report['failures']))
        ->toContain('Line 3: staff_id is required')
        ->toContain('Line 4: department_code "NOPE" was not found');

    expect(User::where('staff_id', 'LEC100')->value('department_id'))
        ->toBe(Department::where('code', 'JKP')->value('id'))
        ->and(User::where('email', 'no-staff@uthm.edu.my')->exists())->toBeFalse()
        ->and(User::where('staff_id', 'LEC200')->exists())->toBeFalse();
});

test('a course import skips rows without a course code', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $csv = implode("\n", [
        'course_code,course_name_en,course_name_my,credit_hours,course_type_code,course_load,pre_requisite,sc_code,vbe_ept_code,sdg_code',
        ',Orphan Course,,3,,,,,',
        'BIC10001,Valid Course,,3,,,,,',
    ])."\n";

    importCsv($admin, 'curriculum.courses.import', $csv)->assertSessionHasNoErrors();

    $report = session('import_report');

    expect($report['created'])->toBe(1)
        ->and($report['failed'])->toBe(1)
        ->and($report['failures'][0])->toContain('Line 2: course_code is required');

    expect(Course::where('course_code', 'BIC10001')->exists())->toBeTrue();
});

test('the import summary alert shows the success and failure counts', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $csv = "matric_num,student_name,student_email,program_code,plan_code,advisor_id,password\n"
        ."A210010,Good Row,good@student.fsktm.test,,,,\n"
        .",Bad Row,bad@student.fsktm.test,,,,\n";

    $this->actingAs($admin)
        ->from(route('admin.students.index'))
        ->followingRedirects()
        ->post(route('admin.students.import'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])
        ->assertOk()
        ->assertSee('Student import finished with skipped rows')
        ->assertSee('failed')
        ->assertSee('Line 3: matric_num is required');
});
