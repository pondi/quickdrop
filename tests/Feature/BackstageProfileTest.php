<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackstageProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_backstage_admin_routes_are_separate_from_user_routes(): void
    {
        // Backstage admins use different authentication and don't access user profile routes
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this
            ->actingAs($admin)
            ->get('/profile');

        // Should redirect as this is a QuickDrop user route
        $response->assertRedirect('/auth/login');
    }

    public function test_backstage_admin_can_access_backstage_routes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this
            ->actingAs($admin)
            ->get('/backstage/dashboard');

        $response->assertOk();
    }
}