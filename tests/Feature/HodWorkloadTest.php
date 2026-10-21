<?php

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Department;
use App\Models\Offering;
use App\Models\OfferingGroup;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * A Head of Department looking at two departments. The first lecturer takes a
 * course of its own (load 4) and coordinates it (+1); the second lectures one
 * PAK group's section of a lighter course (load 2).
 *
 * @return array{0: User, 1: Department, 2: User, 3: Department, 4: User, 5: User}
 */
function makeHodWithDepartments(): array
{
    Setting::put('active_session', '2025/2026');
    Setting::put('active_semester', '1');

    $program = Program::create(['code' => 'WL', 'name' => 'Workload Programme', 'duration_years' => 3]);
    $plan = CoursePlan::create([
        'program_id' => $program->id,
        'code' => 'WL2025-LOCAL',
        'name' => 'Workload Plan',
    ]);

    $software = Department::create(['code' => 'JKP', 'name' => 'Software Engineering']);
    $multimedia = Department::create(['code' => 'JMM', 'name' => 'Multimedia']);

    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true, 'department_id' => $software->id]);
    $softwareLecturer = User::factory()->create([
        'role' => 'advisor', 'name' => 'Dr. Software', 'department_id' => $software->id,
    ]);
    $multimediaLecturer = User::factory()->create([
        'role' => 'advisor', 'name' => 'Dr. Multimedia', 'department_id' => $multimedia->id,
    ]);

    $own = Course::create([
        'code' => 'WL 10103', 'name' => 'Own Course', 'credits' => 3, 'course_load' => 4,
        'semester' => 1, 'course_plan_id' => $plan->id,
    ]);

    // One offering the first lecturer lectures and coordinates.
    Offering::create([
        'course_id' => $own->id, 'session' => '2025/2026', 'semester' => '1',
        'section' => '01', 'capacity' => 40, 'is_offered' => true, 'year' => 1,
        'lecturer_id' => $softwareLecturer->id, 'coordinator_id' => $softwareLecturer->id,
    ]);

    $shared = Course::create([
        'code' => 'WL 20103', 'name' => 'Shared Course', 'credits' => 3, 'course_load' => 2,
        'semester' => 1, 'course_plan_id' => $plan->id,
    ]);

    // …and one the second lecturer only takes as a PAK group's section: the
    // assignment the Head of Department makes in Manage Offering.
    $offering = Offering::create([
        'course_id' => $shared->id, 'session' => '2025/2026', 'semester' => '1',
        'section' => '01', 'capacity' => 40, 'is_offered' => true, 'year' => 1,
    ]);

    OfferingGroup::create([
        'offering_id' => $offering->id, 'advisor_id' => $hod->id,
        'section' => '03', 'lecturer_id' => $multimediaLecturer->id,
    ]);

    return [$hod, $software, $softwareLecturer, $multimedia, $multimediaLecturer, $own];
}

test('the workload is built from the assignments made in manage offering', function () {
    [$hod, $software, $softwareLecturer, $multimedia, $multimediaLecturer] = makeHodWithDepartments();

    $html = $this->actingAs($hod)
        ->get(route('advisor.offering.workload'))
        ->assertOk()
        ->assertSee($software->name)
        ->assertSee($multimedia->name)
        ->assertSee('Total sections: 2')
        // 4 for the course it lectures + 1 for coordinating it, 2 for the PAK
        // group section of the other course.
        ->assertSee('Total load: 7')
        ->getContent();

    // The lecturer of the whole course carries its course load and the extra
    // load for coordinating it.
    $softwareRow = Str::before(Str::after($html, $softwareLecturer->name), '</tr>');

    expect($softwareRow)->toContain('WL 10103 (S01)')
        ->and($softwareRow)->toContain('>5<')
        ->and($softwareRow)->toContain('incl. +1 coordinator');

    // A PAK group's own section counts as teaching load for its lecturer too.
    $multimediaRow = Str::before(Str::after($html, $multimediaLecturer->name), '</tr>');

    expect($multimediaRow)->toContain('WL 20103 (S03)')
        ->and($multimediaRow)->toContain('>2<')
        ->and($multimediaRow)->not->toContain('coordinator');
});

test('the workload can be narrowed to one department', function () {
    [$hod, $software, $softwareLecturer] = makeHodWithDepartments();

    $response = $this->actingAs($hod)
        ->get(route('advisor.offering.workload', ['department_id' => $software->id]))
        ->assertOk()
        ->assertSee($softwareLecturer->name)
        ->assertSee('WL 10103')
        // Only the chosen department is left, in the cards and in the totals.
        ->assertDontSee('Dr. Multimedia')
        ->assertDontSee('WL 20103')
        ->assertSee('Total sections: 1')
        ->assertSee('Total load: 5');

    expect($response->getContent())->toContain('value="'.$software->id.'" selected');
});
