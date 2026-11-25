<?php

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Offering;
use App\Models\PlanCourse;
use App\Models\PreRegistration;
use App\Models\Program;
use App\Models\StudyPlanEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('course code cleanup previews without changing data or writing a backup', function () {
    Storage::fake('local');
    $course = Course::create(['code' => 'BIC 10303', 'name' => 'Algebra', 'credits' => 3]);
    Course::create(['code' => 'BIC10303', 'name' => 'Algebra', 'credits' => 3]);

    $this->artisan('courses:normalize-codes --dry-run')->assertSuccessful();

    expect($course->refresh()->code)->toBe('BIC 10303');
    expect(Course::count())->toBe(2);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('cleanup preserves course relationships and registration history with a backup of the originals', function () {
    Storage::fake('local');
    $program = Program::create(['code' => 'CS', 'name' => 'Computing']);
    $plan = CoursePlan::create(['program_id' => $program->id, 'code' => 'CS2026', 'name' => 'Computing Plan']);
    $student = User::factory()->create(['role' => 'student']);
    // Create the unused record first: reference count, not ID or formatting, wins.
    $duplicate = Course::create(['code' => 'UHB33103', 'name' => 'Unused name', 'credits' => 3, 'synopsis' => 'Existing synopsis']);
    $keeper = Course::create(['code' => "UHB \t33103", 'name' => 'In-use name', 'credits' => 2]);
    $dependent = Course::create(['code' => "CS\u{00A0}20103", 'name' => 'Dependent', 'credits' => 3, 'prerequisites' => "UHB 33103, CS\t10103"]);
    $slot = PlanCourse::create(['course_plan_id' => $plan->id, 'course_id' => $keeper->id, 'year' => 1, 'semester' => 1, 'credits' => 2]);
    foreach (['CS2024', 'CS2025'] as $code) {
        $otherPlan = CoursePlan::create(['program_id' => $program->id, 'code' => $code, 'name' => $code]);
        PlanCourse::create(['course_plan_id' => $otherPlan->id, 'course_id' => $keeper->id, 'year' => 1, 'semester' => 1, 'credits' => 2]);
    }
    $entry = StudyPlanEntry::create(['user_id' => $student->id, 'course_id' => $keeper->id, 'elective_slot_id' => $duplicate->id, 'year' => 1, 'semester' => 1, 'status' => 'COMPLETED']);
    $offering = Offering::create(['course_id' => $duplicate->id, 'session' => '20262027', 'semester' => '1']);
    $registration = PreRegistration::create(['user_id' => $student->id, 'offering_id' => $offering->id, 'session' => '20262027', 'semester' => '1', 'status' => 'approved', 'credits' => 2]);
    $requestId = DB::table('course_offering_requests')->insertGetId([
        'user_id' => $student->id, 'course_id' => $duplicate->id, 'session' => '20262027', 'semester' => '2',
        'reason' => 'Needed for graduation', 'status' => 'pending_advisor',
    ]);

    $this->artisan('courses:normalize-codes')->assertSuccessful();

    $this->assertDatabaseMissing('courses', ['id' => $duplicate->id]);
    expect($keeper->refresh()->code)->toBe('UHB33103')
        ->and($keeper->name)->toBe('In-use name')
        ->and($keeper->credits)->toBe(2)
        ->and($keeper->synopsis)->toBe('Existing synopsis');
    expect($dependent->refresh()->code)->toBe('CS20103')
        ->and($dependent->prerequisites)->toBe('UHB33103, CS10103');
    expect($slot->refresh()->course_id)->toBe($keeper->id);
    expect($entry->refresh()->course_id)->toBe($keeper->id)
        ->and($entry->elective_slot_id)->toBe($keeper->id)
        ->and($entry->status)->toBe('COMPLETED');
    expect($offering->refresh()->course_id)->toBe($keeper->id);
    expect($registration->refresh()->offering_id)->toBe($offering->id)
        ->and($registration->status)->toBe('approved');
    $this->assertDatabaseHas('course_offering_requests', ['id' => $requestId, 'course_id' => $keeper->id, 'reason' => 'Needed for graduation']);

    $files = Storage::disk('local')->allFiles('course-code-backups');
    expect($files)->toHaveCount(1);
    $backup = json_decode(Storage::disk('local')->get($files[0]), true, flags: JSON_THROW_ON_ERROR);
    expect($backup['courses'])->toHaveCount(3);
    expect($backup['linked_records']['offerings'][0]['course_id'])->toBe($duplicate->id);

    $this->artisan('courses:normalize-codes')->assertSuccessful();
    expect(Storage::disk('local')->allFiles('course-code-backups'))->toHaveCount(1);
});

test('conflicting request history rolls the entire normalization back rather than deleting history', function () {
    Storage::fake('local');
    $student = User::factory()->create(['role' => 'student']);
    $first = Course::create(['code' => 'CS 10103', 'name' => 'First', 'credits' => 3]);
    $second = Course::create(['code' => 'CS10103', 'name' => 'Second', 'credits' => 3]);
    foreach ([$first, $second] as $course) {
        DB::table('course_offering_requests')->insert([
            'user_id' => $student->id, 'course_id' => $course->id, 'session' => '20262027',
            'semester' => '1', 'reason' => 'Original request', 'status' => 'pending_advisor',
        ]);
    }

    $this->artisan('courses:normalize-codes')->assertFailed();

    expect($first->refresh()->code)->toBe('CS 10103');
    expect($second->refresh()->code)->toBe('CS10103');
    $this->assertDatabaseCount('course_offering_requests', 2);
});
