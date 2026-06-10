<?php

use App\Models\User;

/**
 * Admin back-office is login-protected (CLAUDE.md: auth-protected admin area).
 */

it('redirects guests from the dashboard to the login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
});

it('blocks guests from candidate management', function () {
    $this->get(route('admin.candidates.index'))->assertRedirect(route('admin.login'));
});

it('lets an authenticated admin reach the dashboard', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
});

it('shows the full-screen QR code link to authenticated admins', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.election.edit'))
        ->assertOk()
        ->assertSee('Afficher le QR code en pleine page');

    $this->actingAs($admin)
        ->get(route('admin.election.qr.fullscreen'))
        ->assertOk()
        ->assertSee(route('vote.start'));
});

it('rejects bad credentials', function () {
    User::factory()->create(['email' => 'admin@eurocham.sn', 'password' => bcrypt('correct')]);

    $this->post(route('admin.login.attempt'), [
        'email' => 'admin@eurocham.sn',
        'password' => 'wrong',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
