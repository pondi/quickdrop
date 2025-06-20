<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\QuickDropUser;

class AuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_quickdrop_login_when_not_authenticated()
    {
        $response = $this->get('/');
        
        $response->assertRedirect('/auth/login');
    }

    public function test_login_route_redirects_to_auth_login()
    {
        $response = $this->get('/login');
        
        $response->assertRedirect('/auth/login');
    }

    public function test_backstage_login_route_shows_admin_login()
    {
        $response = $this->get('/backstage/login');
        
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->where('isBackstageLogin', true)
        );
    }

    public function test_protected_routes_redirect_to_correct_login()
    {
        // QuickDrop protected routes should redirect to /auth/login
        $response = $this->get('/dashboard');
        $response->assertRedirect('/auth/login');
        
        $response = $this->get('/quickdrop');
        $response->assertRedirect('/auth/login');
        
        $response = $this->get('/profile');
        $response->assertRedirect('/auth/login');
        
        // Backstage routes should redirect to /backstage/login
        $response = $this->get('/backstage');
        $response->assertRedirect('/backstage/login');
        
        $response = $this->get('/backstage/users');
        $response->assertRedirect('/backstage/login');
    }

    public function test_authenticated_quickdrop_user_can_access_dashboard()
    {
        $user = QuickDropUser::factory()->create();
        
        $response = $this->actingAs($user, 'quickdrop')->get('/dashboard');
        
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Dashboard'));
    }

    public function test_authenticated_admin_can_access_backstage()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/backstage/dashboard');
        
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Backstage/Dashboard'));
    }

    public function test_non_admin_cannot_access_backstage()
    {
        $user = User::factory()->create(['is_admin' => false]);
        
        $response = $this->actingAs($user)->get('/backstage/dashboard');
        
        $response->assertForbidden();
    }

    public function test_root_redirects_to_dashboard_for_authenticated_quickdrop_user()
    {
        $user = QuickDropUser::factory()->create();
        
        $response = $this->actingAs($user, 'quickdrop')->get('/');
        
        $response->assertRedirect('/dashboard');
    }
}