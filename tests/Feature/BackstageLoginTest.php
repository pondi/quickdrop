<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackstageLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_backstage_login_page_loads_without_sql_errors()
    {
        $response = $this->get('/backstage/login');
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->has('isBackstageLogin')
        );
        
        // Check that no database errors occurred
        $this->assertStringNotContainsString('SQLSTATE', $response->content());
        $this->assertStringNotContainsString('Column not found', $response->content());
        $this->assertStringNotContainsString('Base table or view not found', $response->content());
    }

    public function test_backstage_login_form_submission()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true
        ]);

        $response = $this->post('/backstage/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/backstage/dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_backstage_login_with_invalid_credentials()
    {
        $response = $this->post('/backstage/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_non_admin_cannot_access_backstage_after_login()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false
        ]);

        $response = $this->post('/backstage/login', [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        // Non-admin users are logged out and redirected to quickdrop login
        $response->assertRedirect(route('quickdrop.login'));
        $response->assertSessionHas('error', 'Please use the QuickDrop login.');
        $this->assertGuest();
    }

    public function test_backstage_login_redirects_if_already_authenticated()
    {
        $admin = User::factory()->create([
            'is_admin' => true
        ]);

        $this->actingAs($admin);

        $response = $this->get('/backstage/login');
        
        // The guest middleware redirects to dashboard for authenticated users
        $response->assertRedirect('/dashboard');
    }
}