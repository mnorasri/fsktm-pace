<?php

use App\Models\Setting;
use App\Models\User;
use App\Support\RegistrationSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeScheduleAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

test('an administrator can save both registration cutoffs', function () {
    $admin = makeScheduleAdmin();

    $this->actingAs($admin)
        ->put(route('admin.configs.schedule'), [
            'offering_cutoff_date' => '2026-12-01',
            'registration_cutoff_date' => '2026-12-15',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(RegistrationSchedule::offeringCutoff()?->toDateString())->toBe('2026-12-01')
        ->and(RegistrationSchedule::registrationCutoff()?->toDateString())->toBe('2026-12-15');
});

test('the registration cutoff can be cleared to keep that side open', function () {
    $admin = makeScheduleAdmin();

    Setting::put(RegistrationSchedule::OFFERING_CUTOFF_KEY, '2026-12-01');
    Setting::put(RegistrationSchedule::REGISTRATION_CUTOFF_KEY, '2026-12-15');

    $this->actingAs($admin)
        ->put(route('admin.configs.schedule'), [
            'offering_cutoff_date' => '2026-12-01',
            'registration_cutoff_date' => '',
        ])
        ->assertSessionHasNoErrors();

    expect(RegistrationSchedule::registrationCutoff())->toBeNull()
        ->and(RegistrationSchedule::offeringCutoff()?->toDateString())->toBe('2026-12-01');
});

test('the registration cutoff cannot fall before the offering cutoff', function () {
    $admin = makeScheduleAdmin();

    $this->actingAs($admin)
        ->put(route('admin.configs.schedule'), [
            'offering_cutoff_date' => '2026-12-15',
            'registration_cutoff_date' => '2026-12-01',
        ])
        ->assertSessionHasErrors('registration_cutoff_date');

    expect(RegistrationSchedule::offeringCutoff())->toBeNull()
        ->and(RegistrationSchedule::registrationCutoff())->toBeNull();
});

test('the configs page shows the saved cutoffs', function () {
    $admin = makeScheduleAdmin();

    Setting::put(RegistrationSchedule::OFFERING_CUTOFF_KEY, '2026-12-01');
    Setting::put(RegistrationSchedule::REGISTRATION_CUTOFF_KEY, '2026-12-15');

    $this->actingAs($admin)
        ->get(route('admin.configs.index'))
        ->assertOk()
        ->assertSee('Registration Schedule')
        ->assertSee('2026-12-01')
        ->assertSee('2026-12-15');
});

test('every portal shows the schedule in the top bar', function () {
    Setting::put(RegistrationSchedule::OFFERING_CUTOFF_KEY, now()->addDay()->toDateString());
    Setting::put(RegistrationSchedule::REGISTRATION_CUTOFF_KEY, now()->addDays(10)->toDateString());

    $student = User::factory()->create(['role' => 'student']);
    $lecturer = User::factory()->create(['role' => 'advisor']);

    foreach ([$student, $lecturer] as $user) {
        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Course offering:')
            ->assertSee(now()->addDay()->format('d M Y'))
            ->assertSee('Student registration:')
            ->assertSee(now()->addDays(10)->format('d M Y'));
    }
});
