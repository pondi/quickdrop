<?php

use App\Models\QuickDropUser;
use App\Models\MagicLink;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

test('login page can be rendered', function () {
    $response = $this->get(route('quickdrop.login'));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('QuickDropAuth/Login')
        );
});

test('register page can be rendered', function () {
    $response = $this->get(route('quickdrop.register'));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('QuickDropAuth/Register')
        );
});

test('new user can request magic link', function () {
    $email = 'newuser@example.com';
    
    $response = $this->post(route('quickdrop.magic-link'), [
        'email' => $email,
        'name' => 'New User',
        'purpose' => 'register',
    ]);
    
    $response->assertRedirect()
        ->assertSessionHas('success', 'Magic link sent! Please check your email.');
    
    $this->assertDatabaseHas('quickdrop_users', [
        'email' => $email,
        'name' => 'New User',
    ]);
    
    $user = QuickDropUser::where('email', $email)->first();
    
    $this->assertDatabaseHas('magic_links', [
        'quickdrop_user_id' => $user->id,
        'used_at' => null,
    ]);
    
    Notification::assertSentTo($user, MagicLinkNotification::class);
});

test('existing user can request magic link', function () {
    $user = QuickDropUser::factory()->create();
    
    $response = $this->post(route('quickdrop.magic-link'), [
        'email' => $user->email,
        'purpose' => 'login',
    ]);
    
    $response->assertRedirect()
        ->assertSessionHas('success', 'Magic link sent! Please check your email.');
    
    $this->assertDatabaseHas('magic_links', [
        'quickdrop_user_id' => $user->id,
        'used_at' => null,
    ]);
    
    Notification::assertSentTo($user, MagicLinkNotification::class);
});

test('magic link validation requires email', function () {
    $response = $this->post(route('quickdrop.magic-link'), []);
    
    $response->assertSessionHasErrors(['email']);
});

test('user can authenticate with valid magic link', function () {
    $user = QuickDropUser::factory()->create();
    $magicLink = MagicLink::factory()->create([
        'quickdrop_user_id' => $user->id,
        'email' => $user->email,
    ]);
    
    $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
        'quickdrop.auth.verify',
        $magicLink->expires_at,
        ['token' => $magicLink->token]
    );
    
    $response = $this->get($url);
    
    $response->assertRedirect(route('dashboard'));
    
    $this->assertAuthenticatedAs($user, 'quickdrop');
    
    $magicLink->refresh();
    $this->assertNotNull($magicLink->used_at);
});

test('expired magic link cannot be used', function () {
    $user = QuickDropUser::factory()->create();
    $magicLink = MagicLink::factory()->expired()->create([
        'quickdrop_user_id' => $user->id,
        'email' => $user->email,
    ]);
    
    $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
        'quickdrop.auth.verify',
        $magicLink->expires_at,
        ['token' => $magicLink->token]
    );
    
    $response = $this->get($url);
    
    $response->assertRedirect(route('quickdrop.login'))
        ->assertSessionHasErrors(['email']);
    
    $this->assertGuest('quickdrop');
});

test('used magic link cannot be reused', function () {
    $user = QuickDropUser::factory()->create();
    $magicLink = MagicLink::factory()->used()->create([
        'quickdrop_user_id' => $user->id,
        'email' => $user->email,
    ]);
    
    $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
        'quickdrop.auth.verify',
        $magicLink->expires_at,
        ['token' => $magicLink->token]
    );
    
    $response = $this->get($url);
    
    $response->assertRedirect(route('quickdrop.login'))
        ->assertSessionHasErrors(['email']);
    
    $this->assertGuest('quickdrop');
});

test('invalid magic link token shows error', function () {
    $response = $this->get(route('quickdrop.auth.verify', 'invalid-token'));
    
    $response->assertRedirect(route('quickdrop.login'))
        ->assertSessionHasErrors(['email' => 'Invalid or expired magic link.']);
    
    $this->assertGuest('quickdrop');
});

test('authenticated user can logout', function () {
    $user = actingAsQuickDropUser();
    
    $response = $this->post(route('quickdrop.logout'));
    
    $response->assertRedirect('/');
    $this->assertGuest('quickdrop');
});

test('unauthenticated user cannot access protected routes', function () {
    $response = $this->get(route('dashboard'));
    
    $response->assertRedirect(route('quickdrop.login'));
});

test('authenticated user can access dashboard', function () {
    actingAsQuickDropUser();
    
    $response = $this->get(route('dashboard'));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
        );
});

test('unverified user gets reminder to verify email', function () {
    $user = QuickDropUser::factory()->unverified()->create();
    
    $this->actingAs($user, 'quickdrop')
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.email_verified_at', null)
        );
});

test('user can resend verification email', function () {
    $user = actingAsQuickDropUser(['email_verified_at' => null]);
    
    $response = $this->post(route('quickdrop.resend-verification'));
    
    $response->assertRedirect()
        ->assertSessionHas('success', 'Verification email sent!');
    
    Notification::assertSentTo($user, MagicLinkNotification::class);
});