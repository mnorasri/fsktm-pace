<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseOfferingRequest;
use App\Models\Offering;
use App\Models\PreRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseRequestRegistrationService
{
    public function __construct(private readonly CurriculumService $curriculum) {}

    /**
     * Register only the requested course, not the student's whole term.
     * Also used to reconcile requests approved before automatic registration.
     */
    public function register(CourseOfferingRequest $request): PreRegistration
    {
        return DB::transaction(function () use ($request) {
            $course = Course::whereKey($request->course_id)->lockForUpdate()->firstOrFail();
            $record = CourseOfferingRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $student = User::whereKey($record->user_id)->lockForUpdate()->firstOrFail();

            if ($record->status !== CourseOfferingRequest::APPROVED) {
                throw ValidationException::withMessages(['course_request' => 'Only a HoD-approved request can register a student.']);
            }

            if (! $course->is_active) {
                throw ValidationException::withMessages(['course_request' => 'This course is inactive and cannot be offered.']);
            }

            $offering = Offering::firstOrCreate(
                ['course_id' => $course->id, 'session' => $record->session, 'semester' => $record->semester],
                ['section' => '01', 'capacity' => 40, 'is_offered' => true],
            );
            if (! $offering->is_offered) {
                $offering->update(['is_offered' => true]);
            }

            $registration = PreRegistration::where('user_id', $student->id)->where('offering_id', $offering->id)->first();
            if ($registration?->status === 'approved') {
                return $registration;
            }

            $prerequisites = $this->curriculum->prerequisitesSatisfied($student, $course);
            if (! $prerequisites['ok']) {
                throw ValidationException::withMessages([
                    'course_request' => 'Complete prerequisite(s) before registration: '.implode(', ', $prerequisites['missing']).'.',
                ]);
            }

            $committed = $this->curriculum->totalCommittedCredits($student, $record->session, $record->semester, $course->id);
            $alreadyCounted = $registration?->status === 'pending' ? $registration->credits : 0;
            if ($committed - $alreadyCounted + $course->credits > CurriculumService::MAX_CREDITS_PER_TERM) {
                throw ValidationException::withMessages([
                    'course_request' => 'Registering this course would exceed the '.CurriculumService::MAX_CREDITS_PER_TERM.'-credit term limit.',
                ]);
            }

            return PreRegistration::updateOrCreate(
                ['user_id' => $student->id, 'offering_id' => $offering->id],
                [
                    'session' => $record->session,
                    'semester' => $record->semester,
                    'credits' => $course->credits,
                    'status' => 'approved',
                    'reviewed_by' => $record->hod_reviewed_by,
                    'reviewed_at' => $record->hod_reviewed_at ?? now(),
                    'note' => 'Registered via approved course request #'.$record->id.'.',
                ],
            );
        });
    }
}
