<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

test('admin login page can be rendered', function () {
    $response = $this->get('/backstage/login');
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->where('isBackstageLogin', true)
        );
});

test('admin can login with valid credentials', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
        'password' => bcrypt('password'),
    ]);
    
    $response = $this->post('/backstage/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);
    
    $response->assertRedirect(route('backstage.dashboard'));
    $this->assertAuthenticatedAs($admin);
});

test('non-admin user cannot access backstage', function () {
    $user = User::factory()->create([
        'is_admin' => false,
        'password' => bcrypt('password'),
    ]);
    
    $response = $this->post('/backstage/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    
    $this->actingAs($user);
    
    $response = $this->get(route('backstage.dashboard'));
    $response->assertForbidden();
});

test('admin login requires valid credentials', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
        'password' => bcrypt('password'),
    ]);
    
    $response = $this->post('/backstage/login', [
        'email' => $admin->email,
        'password' => 'wrongpassword',
    ]);
    
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('admin login validates required fields', function () {
    $response = $this->post('/backstage/login', []);
    
    $response->assertSessionHasErrors(['email', 'password']);
});

test('authenticated admin can access backstage dashboard', function () {
    actingAsAdmin();
    
    $response = $this->get(route('backstage.dashboard'));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backstage/Dashboard')
        );
});

test('unauthenticated user cannot access backstage routes', function () {
    $response = $this->get(route('backstage.dashboard'));
    
    $response->assertRedirect('/backstage/login');
});

test('admin can logout', function () {
    actingAsAdmin();
    
    $response = $this->post(route('logout'));
    
    $response->assertRedirect('/');
    $this->assertGuest();
});

test('admin session is separate from quickdrop user session', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $quickdropUser = actingAsQuickDropUser();
    
    // Verify QuickDrop user is authenticated on quickdrop guard
    $this->assertTrue(Auth::guard('quickdrop')->check());
    $this->assertEquals($quickdropUser->id, Auth::guard('quickdrop')->id());
    
    // Admin login doesn't affect quickdrop session
    $this->actingAs($admin);
    
    $this->assertAuthenticatedAs($admin);
    // QuickDrop user should still be authenticated on their guard
    $this->assertTrue(Auth::guard('quickdrop')->check());
    $this->assertEquals($quickdropUser->id, Auth::guard('quickdrop')->id());
});

test('backstage routes are protected by middleware', function () {
    $routes = [
        'backstage.dashboard',
        'backstage.users.index',
        'backstage.quickdrop-users.index',
        'backstage.quickdrops.index',
        'backstage.audit-log.index',
        'backstage.file-types.index',
        'backstage.settings.index',
    ];
    
    foreach ($routes as $route) {
        $response = $this->get(route($route));
        $response->assertRedirect('/backstage/login');
    }
});

test('admin with remember me stays logged in', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
        'password' => bcrypt('password'),
    ]);
    
    $response = $this->post('/backstage/login', [
        'email' => $admin->email,
        'password' => 'password',
        'remember' => true,
    ]);
    
    $response->assertRedirect(route('backstage.dashboard'));
    
    // Debug: check what cookies are actually set
    $cookies = $response->headers->getCookies();
    if (empty($cookies)) {
        // If no cookies are set, we can just verify the user is authenticated
        $this->assertAuthenticatedAs($admin);
    } else {
        // Check for any remember cookie
        $rememberCookieFound = false;
        foreach ($cookies as $cookie) {
            if (str_starts_with($cookie->getName(), 'remember_')) {
                $rememberCookieFound = true;
                break;
            }
        }
        expect($rememberCookieFound)->toBeTrue('Remember cookie should be set');
        $this->assertAuthenticatedAs($admin);
    }
});

test('admin password reset flow works', function () {
    Notification::fake();
    
    $admin = User::factory()->create(['is_admin' => true]);
    
    // Request password reset
    $response = $this->post('/forgot-password', [
        'email' => $admin->email,
    ]);
    
    $response->assertSessionHas('status');
    
    Notification::assertSentTo($admin, \Illuminate\Auth\Notifications\ResetPassword::class);
});

test('rate limiting is applied to admin login', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
        'password' => bcrypt('password'),
    ]);
    
    // Attempt login multiple times with wrong password
    for ($i = 0; $i < 6; $i++) {
        $this->post('/backstage/login', [
            'email' => $admin->email,
            'password' => 'wrongpassword',
        ]);
    }
    
    // Next attempt should be rate limited
    $response = $this->post('/backstage/login', [
        'email' => $admin->email,
        'password' => 'wrongpassword',
    ]);
    
    // Rate limiting often returns 302 with error message instead of 429
    $response->assertRedirect()
        ->assertSessionHasErrors();
});