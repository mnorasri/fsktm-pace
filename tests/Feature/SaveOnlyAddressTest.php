<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * Some addresses in the portals only accept a form save (POST, PUT or DELETE).
 * A reload, a bookmark or a shared link opens them with GET, which Laravel would
 * answer with a "405 Method Not Allowed" error page.
 */

test('a GET on a save-only address returns the visitor to their dashboard', function () {
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);

    $this->actingAs($hod)
        ->get('/advisor/offering/40/group')
        ->assertRedirect(route('advisor.dashboard'))
        ->assertSessionHas('notice');

    $this->actingAs($hod)
        ->get('/advisor/offering/40')
        ->assertRedirect(route('advisor.dashboard'));
});

test('a guest who opens a save-only address is sent to the login page', function () {
    $this->get('/student/study-plan/entries/5/elective')->assertRedirect(route('login'));
});

test('a save-only address still answers its own verb', function () {
    $hod = User::factory()->create(['role' => 'advisor', 'is_hod' => true]);

    // The address exists for PUT, so asking for it with GET is not a 405.
    $this->actingAs($hod)->get('/advisor/offering/40/group')->assertRedirect();
    $this->actingAs($hod)->put('/advisor/offering/40/group')->assertNotFound();
});

test('addresses that exist nowhere still answer 404', function () {
    $this->get('/nothing-here')->assertNotFound();
    $this->post('/nothing-here')->assertNotFound();
});
