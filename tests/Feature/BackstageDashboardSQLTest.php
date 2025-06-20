<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\QuickDropUser;
use App\Models\UploadRequest;
use App\Models\UploadObject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackstageDashboardSQLTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run seeders
        $this->artisan('db:seed', ['--class' => 'SystemSettingsSeeder']);
        $this->artisan('db:seed', ['--class' => 'FileTypeSettingsSeeder']);
    }

    public function test_dashboard_loads_without_sql_errors()
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email' => 'admin@example.com'
        ]);

        // Create some test data
        $quickDropUser = QuickDropUser::factory()->create();
        $uploadRequest = UploadRequest::factory()->create([
            'quickdrop_user_id' => $quickDropUser->id
        ]);
        
        // Create and attach upload objects
        $uploadObjects = UploadObject::factory()->count(3)->create([
            'quickdrop_owner_id' => $quickDropUser->id
        ]);
        
        foreach ($uploadObjects as $object) {
            $uploadRequest->uploadObjects()->attach($object->id);
        }

        $response = $this->actingAs($admin)->get('/backstage/dashboard');
        
        $response->assertStatus(200);
        
        // Check for SQL errors in the response
        $content = $response->content();
        $this->assertStringNotContainsString('SQLSTATE', $content);
        $this->assertStringNotContainsString('Column not found', $content);
        $this->assertStringNotContainsString('Base table or view not found', $content);
        $this->assertStringNotContainsString('Unknown column', $content);
        
        // Verify the dashboard renders with expected data
        $response->assertInertia(function ($page) {
            $page->component('Backstage/Dashboard')
                 ->has('statistics');
        });
    }

    public function test_dashboard_statistics_query()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        // Test the actual statistics endpoint
        $response = $this->actingAs($admin)->get('/backstage/api/stats');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'overview',
            'daily_stats',
            'file_types',
            'top_users',
            'recent_activity',
            'top_downloads'
        ]);
    }
}