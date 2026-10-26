<?php

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Offering;
use App\Models\OfferingGroup;
use App\Models\PreRegistration;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * A Head of Department looking after one offered course that two PAK groups
 * have already registered for: "Prof. Norasri" with two students and
 * "Dr. Nurezayana" with one.
 *
 * @return array{0: User, 1: Offering, 2: User, 3: User}
 */
function makeOfferingWithPakGroups(): array
{
    Setting::put('active_session', '2025/2026');
    Setting::put('active_semester', '1');

    $program = Program::create(['code' => 'PAK', 'name' => 'PAK Programme', 'duration_years' => 3]);
    $plan = CoursePlan::create([
        'program_id' => $program->id,
        'code' => 'PAK2025-LOCAL',
        'name' => 'PAK Plan',
    ]);

    $course = Course::create([
        'code' => 'PAK 10103', 'name' => 'Offered Course', 'credits' => 3,
        'semester' => 1, 'course_plan_id' => $plan->id,
    ]);

    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);
    $norasri = User::factory()->create(['role' => 'advisor', 'name' => 'Prof. Norasri', 'staff_id' => 'norasri']);
    $nurezayana = User::factory()->create(['role' => 'advisor', 'name' => 'Dr. Nurezayana', 'staff_id' => 'nurezayana']);

    $offering = Offering::create([
        'course_id' => $course->id, 'session' => '2025/2026', 'semester' => '1',
        'section' => '01', 'capacity' => 40, 'is_offered' => true,
    ]);

    // Two students of the first PAK group, one of the second.
    foreach ([$norasri, $norasri, $nurezayana] as $advisor) {
        $student = User::factory()->create([
            'role' => 'student',
            'program_id' => $program->id,
            'plan_id' => $plan->id,
            'advisor_id' => $advisor->staff_id,
        ]);

        PreRegistration::create([
            'user_id' => $student->id, 'offering_id' => $offering->id,
            'session' => '2025/2026', 'semester' => '1',
            'credits' => 3, 'status' => 'approved',
        ]);
    }

    return [$hod, $offering, $norasri, $nurezayana];
}

test('manage offering totals the students registered by each PAK group', function () {
    [$hod, , $norasri, $nurezayana] = makeOfferingWithPakGroups();

    $this->actingAs($hod)
        ->get(route('advisor.offering.manage'))
        ->assertOk()
        // The columns of the assignment table.
        ->assertSee('Course Code')
        ->assertSee('Course Name')
        ->assertSee('Registered by PAK group')
        ->assertSee('Coordinator')
        ->assertSee('Whole course')
        // The students of each PAK group next to the group itself.
        ->assertSee($norasri->name)
        ->assertSee('2 student(s)')
        ->assertSee($nurezayana->name)
        ->assertSee('1 student(s)')
        // The page also totals every PAK group across the term.
        ->assertSee('Registrations by PAK group')
        ->assertSee('3 registration(s)');
});

test('a PAK group is given a section from 01 to 10', function () {
    [$hod, , $norasri] = makeOfferingWithPakGroups();

    $html = $this->actingAs($hod)->get(route('advisor.offering.manage'))->assertOk()->getContent();

    // The section select of the first PAK group row.
    $options = Str::before(Str::after($html, 'aria-label="Section for '.$norasri->name.'"'), '</select>');

    foreach (['01', '05', '10'] as $section) {
        expect($options)->toContain('value="'.$section.'"');
    }

    expect($options)->not->toContain('value="11"');
});

test('the manage page can be narrowed to one course group', function () {
    [$hod] = makeOfferingWithPakGroups();

    $plan = CoursePlan::firstOrFail();

    // A second course in another group — "OTH" beside the helper's "PAK".
    $other = Course::create([
        'code' => 'OTH 10103', 'name' => 'Other Group Course', 'credits' => 3,
        'semester' => 1, 'course_plan_id' => $plan->id,
    ]);

    Offering::create([
        'course_id' => $other->id, 'session' => '2025/2026', 'semester' => '1',
        'section' => '01', 'capacity' => 40, 'is_offered' => true,
    ]);

    // Both groups are on the page, and both are offered in the filter…
    $this->actingAs($hod)
        ->get(route('advisor.offering.manage'))
        ->assertOk()
        ->assertSee('PAK · Course Group')
        ->assertSee('PAK 10103')
        ->assertSee('OTH · Course Group')
        ->assertSee('OTH 10103')
        ->assertSee('All course groups');

    // …and choosing one leaves only its courses on the page.
    $response = $this->actingAs($hod)
        ->get(route('advisor.offering.manage', ['group' => 'OTH']))
        ->assertOk()
        ->assertSee('OTH · Course Group')
        ->assertSee('OTH 10103')
        ->assertDontSee('PAK 10103');

    expect($response->getContent())->toContain('value="OTH" selected');
});

test('the HoD can give one PAK group its own section and lecturer', function () {
    [$hod, $offering, $norasri] = makeOfferingWithPakGroups();

    $lecturer = User::factory()->create(['role' => 'advisor', 'name' => 'Dr. Group Lecturer']);

    $this->actingAs($hod)
        ->put(route('advisor.offering.group', $offering), [
            'advisor_id' => $norasri->id,
            'section' => '02',
            'lecturer_id' => $lecturer->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $group = OfferingGroup::where('offering_id', $offering->id)
        ->where('advisor_id', $norasri->id)
        ->firstOrFail();

    expect($group->section)->toBe('02')
        ->and($group->lecturer_id)->toBe($lecturer->id);

    // The group already has a section: saving again updates it in place.
    $this->actingAs($hod)
        ->put(route('advisor.offering.group', $offering), [
            'advisor_id' => $norasri->id,
            'section' => '03',
            'lecturer_id' => $lecturer->id,
        ])
        ->assertSessionHasNoErrors();

    expect(OfferingGroup::where('offering_id', $offering->id)->count())->toBe(1);

    $this->actingAs($hod)
        ->get(route('advisor.offering.manage'))
        ->assertOk()
        ->assertSee('value="03"', false)
        ->assertSee($lecturer->name);
});

test('a coordinator is chosen from the lecturers assigned to the course', function () {
    [$hod, $offering, $norasri, $nurezayana] = makeOfferingWithPakGroups();

    // The coordinator select of the only offering on the page.
    $coordinatorOptions = function () use ($hod) {
        $html = $this->actingAs($hod)->get(route('advisor.offering.manage'))->assertOk()->getContent();

        // Str::between() would run on to the last select in the document.
        return Str::before(Str::after($html, 'name="coordinator_id"'), '</select>');
    };

    // Nothing assigned yet — no lecturer may be coordinated.
    expect($coordinatorOptions())
        ->toContain('disabled')
        ->not->toContain($norasri->name)
        ->not->toContain($nurezayana->name);

    // The offering's own lecturer is a candidate.
    $offering->update(['lecturer_id' => $norasri->id]);

    expect($coordinatorOptions())
        ->toContain($norasri->name)
        ->not->toContain($nurezayana->name);

    // A PAK group lecturer joins the list...
    $this->actingAs($hod)
        ->put(route('advisor.offering.group', $offering), [
            'advisor_id' => $nurezayana->id,
            'section' => '02',
            'lecturer_id' => $nurezayana->id,
        ])
        ->assertSessionHasNoErrors();

    expect($coordinatorOptions())
        ->toContain($norasri->name)
        ->toContain($nurezayana->name);

    // ...and a coordinator already saved is never dropped from its own list.
    $legacy = User::factory()->create(['role' => 'advisor', 'name' => 'Dr. Legacy Coordinator']);
    $offering->update(['coordinator_id' => $legacy->id]);

    expect($coordinatorOptions())
        ->toContain($legacy->name)
        ->toContain($norasri->name)
        ->toContain($nurezayana->name);
});
