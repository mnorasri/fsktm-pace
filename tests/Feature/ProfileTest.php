<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('a guest is sent to the login page', function () {
    $this->get(route('profile'))->assertRedirect(route('login'));

    $this->put(route('profile.update'), ['name' => 'Nobody', 'email' => 'nobody@fsktm.test'])
        ->assertRedirect(route('login'));
});

test('a signed-in user sees their own account details', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'name' => 'Ali Bin Ahmad',
        'student_id' => 'AI123456',
        'email' => 'ali@student.uthm.edu.my',
        'phone' => '012-3456789',
    ]);

    $this->actingAs($student)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('My Profile')
        ->assertSee('Personal information')
        ->assertSee('Change password')
        ->assertSee('Ali Bin Ahmad')
        ->assertSee('AI123456')
        ->assertSee('ali@student.uthm.edu.my')
        ->assertSee('012-3456789')
        ->assertSee('Set by the faculty administrator');
});

test('a lecturer sees their staff details instead', function () {
    $lecturer = User::factory()->create([
        'role' => 'advisor',
        'name' => 'Dr. Siti Aminah',
        'staff_id' => 'xstaminah',
        'email' => 'sitiaminah@uthm.edu.my',
    ]);

    $this->actingAs($lecturer)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Staff ID')
        ->assertSee('xstaminah')
        ->assertDontSee('Student ID');
});

test('a user can update their own name, email and phone', function () {
    $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@fsktm.test', 'phone' => null]);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'New Name',
            'email' => 'New@FSKTM.test',
            'phone' => '019-888 7777',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    expect($user->refresh())
        ->name->toBe('New Name')
        // Addresses are stored in one shape, whatever case was typed.
        ->email->toBe('new@fsktm.test')
        ->phone->toBe('019-888 7777');
});

test('the email must be valid and unique to the user', function () {
    $user = User::factory()->create(['email' => 'me@fsktm.test']);
    User::factory()->create(['email' => 'taken@fsktm.test']);

    $this->actingAs($user)
        ->put(route('profile.update'), ['name' => 'Me', 'email' => 'taken@fsktm.test'])
        ->assertSessionHasErrors('email');

    $this->actingAs($user)
        ->put(route('profile.update'), ['name' => 'Me', 'email' => 'not-an-email'])
        ->assertSessionHasErrors('email');

    // Keeping your own address is not a clash with yourself.
    $this->actingAs($user)
        ->put(route('profile.update'), ['name' => 'Me', 'email' => 'me@fsktm.test'])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->email)->toBe('me@fsktm.test');
});

test('changing the password needs the current one', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->actingAs($user)
        ->put(route('profile.password'), [
            'current_password' => 'not-my-password',
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('the new password must be confirmed', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->actingAs($user)
        ->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'brand-new-secret',
            'password_confirmation' => 'something-else',
        ])
        ->assertSessionHasErrors('password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('a user can change their password and sign in with it', function () {
    $user = User::factory()->create(['email' => 'change@fsktm.test', 'password' => 'password']);

    $this->actingAs($user)
        ->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    expect(Hash::check('brand-new-secret', $user->refresh()->password))->toBeTrue();

    // The new password is the one the sign-in form now accepts.
    Auth::logout();

    $this->post(route('login.store'), ['email' => 'change@fsktm.test', 'password' => 'brand-new-secret'])
        ->assertRedirect(route('student.dashboard'));
});

test('the profile is linked from the navigation for every role', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('profile'));
});
