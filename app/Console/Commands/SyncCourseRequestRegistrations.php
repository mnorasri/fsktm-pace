<?php

namespace App\Console\Commands;

use App\Models\CourseOfferingRequest;
use App\Models\Setting;
use App\Services\CourseRequestRegistrationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\ValidationException;

#[Signature('course-requests:sync-registrations {--dry-run : Count requests without changing registrations}')]
#[Description('Register students for previously approved course requests in the active term')]
class SyncCourseRequestRegistrations extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CourseRequestRegistrationService $registrations): int
    {
        $session = Setting::get('active_session', '2025/2026');
        $semester = Setting::get('active_semester', '1');
        $query = CourseOfferingRequest::where('status', CourseOfferingRequest::APPROVED)
            ->where('session', $session)->where('semester', $semester)
            ->whereNotExists(function (Builder $query) {
                $query->selectRaw('1')->from('pre_registrations')
                    ->join('offerings', 'offerings.id', '=', 'pre_registrations.offering_id')
                    ->whereColumn('pre_registrations.user_id', 'course_offering_requests.user_id')
                    ->whereColumn('offerings.course_id', 'course_offering_requests.course_id')
                    ->whereColumn('offerings.session', 'course_offering_requests.session')
                    ->whereColumn('offerings.semester', 'course_offering_requests.semester')
                    ->where('pre_registrations.status', 'approved');
            });

        if ($this->option('dry-run')) {
            $this->info($query->count().' approved request(s) need registration for '.$session.' · Semester '.$semester.'.');

            return self::SUCCESS;
        }

        $registered = 0;
        $failed = 0;
        foreach ($query->lazyById(100) as $record) {
            try {
                $registrations->register($record);
                $registered++;
            } catch (ValidationException $exception) {
                $failed++;
                $this->error('Request #'.$record->id.': '.$exception->getMessage());
            }
        }

        $this->info($registered.' student registration(s) synchronized; '.$failed.' failed.');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
