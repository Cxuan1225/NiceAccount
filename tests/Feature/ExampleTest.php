<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

//
// Helpers
//
function verifiedUser() : User {
    return User::factory()->create([
        'email_verified_at' => now(),
    ]);
}

//
// HOME (/)
//
test('home redirects guest to login or register (depending on registration)', function () {
    $response = $this->get(route('home'));

    // Your home route redirects to either register or login.
    // We assert it redirects and target is one of them.
    $response->assertRedirect();

    $location = $response->headers->get('Location');

    expect($location)->toBeIn([
        route('login'),
        route('register'),
    ]);
});

test('home redirects authenticated user to dashboard', function () {
    $user = verifiedUser();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});

//
// DASHBOARD
//
test('dashboard redirects guest to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('dashboard returns 200 for verified user and renders inertia dashboard', function () {
    $user = verifiedUser();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
});


