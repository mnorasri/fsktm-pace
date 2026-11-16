<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * The two dates that drive the registration cycle:
 *
 *  - the HoD may publish offered courses up to the course-offering cutoff;
 *  - students may choose courses from the day after that cutoff until the
 *    student-registration cutoff (inclusive).
 *
 * When a date is not configured that side of the window stays open, which
 * keeps the previous behaviour until an administrator sets the schedule.
 */
class RegistrationSchedule
{
    public const OFFERING_CUTOFF_KEY = 'offering_cutoff_date';

    public const REGISTRATION_CUTOFF_KEY = 'registration_cutoff_date';

    public static function offeringCutoff(): ?CarbonImmutable
    {
        return static::date(self::OFFERING_CUTOFF_KEY);
    }

    public static function registrationCutoff(): ?CarbonImmutable
    {
        return static::date(self::REGISTRATION_CUTOFF_KEY);
    }

    /**
     * The HoD may still change offerings up to and including the cutoff date.
     */
    public static function offeringWindowOpen(): bool
    {
        $cutoff = static::offeringCutoff();

        return $cutoff === null || CarbonImmutable::today()->lte($cutoff);
    }

    public static function offeringClosedReason(): ?string
    {
        $cutoff = static::offeringCutoff();

        if ($cutoff === null || CarbonImmutable::today()->lte($cutoff)) {
            return null;
        }

        return 'Course offerings were closed on '.$cutoff->format('d M Y')
            .'. Only an administrator can change them now.';
    }

    /**
     * Students can choose courses from the day after the offering cutoff until
     * the registration cutoff.
     */
    public static function studentWindowOpen(): bool
    {
        return static::studentClosedReason() === null;
    }

    public static function studentClosedReason(): ?string
    {
        $offering = static::offeringCutoff();
        $registration = static::registrationCutoff();
        $today = CarbonImmutable::today();

        if ($offering !== null && $today->lte($offering)) {
            return 'Course registration opens on '.$offering->addDay()->format('d M Y')
                .', after the course-offering cutoff.';
        }

        if ($registration !== null && $today->gt($registration)) {
            return 'Course registration closed on '.$registration->format('d M Y').'.';
        }

        return null;
    }

    protected static function date(string $key): ?CarbonImmutable
    {
        $value = Setting::get($key);

        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
