<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\QuickDropUser;
use App\Models\UploadRequest;
use App\Models\UploadObject;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BackstageSystemTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user
        $this->admin = User::factory()->create([
            'is_admin' => true,
            'email' => 'admin@example.com',
            'password' => bcrypt('password')
        ]);
    }

    public function test_admin_can_access_backstage_and_navigate_all_sections()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/backstage/login')
                    ->type('email', 'admin@example.com')
                    ->type('password', 'password')
                    ->press('Sign in')
                    ->waitForLocation('/backstage/dashboard')
                    ->assertSee('Dashboard')
                    ->assertSee('QuickDrop')
                    ->assertSee('Backstage');

            // Check navigation items are visible
            $browser->assertSee('Dashboard')
                    ->assertSee('Backstage Users')
                    ->assertSee('QuickDrop Users')
                    ->assertSee('QuickDrops')
                    ->assertSee('Audit Log')
                    ->assertSee('File Types')
                    ->assertSee('Settings');

            // Test Dashboard
            $browser->click('a[href="' . route('backstage.dashboard') . '"]')
                    ->waitForLocation('/backstage/dashboard')
                    ->assertSee('System Overview')
                    ->assertSee('Total Users')
                    ->assertSee('Total QuickDrops')
                    ->assertSee('Total Files');

            // Test Backstage Users
            $browser->click('a[href="' . route('backstage.users.index') . '"]')
                    ->waitForLocation('/backstage/users')
                    ->assertSee('Backstage Users')
                    ->assertSee('Add New User')
                    ->assertSee($this->admin->name);

            // Test QuickDrop Users
            $browser->click('a[href="' . route('backstage.quickdrop-users.index') . '"]')
                    ->waitForLocation('/backstage/quickdrop-users')
                    ->assertSee('QuickDrop Users')
                    ->assertSee('Add New User');

            // Test QuickDrops
            $browser->click('a[href="' . route('backstage.quickdrops.index') . '"]')
                    ->waitForLocation('/backstage/quickdrops')
                    ->assertSee('QuickDrops')
                    ->assertSee('Export');

            // Test Audit Log
            $browser->click('a[href="' . route('backstage.audit-log.index') . '"]')
                    ->waitForLocation('/backstage/audit-log')
                    ->assertSee('Audit Log')
                    ->assertSee('Search');

            // Test File Types
            $browser->click('a[href="' . route('backstage.file-types.index') . '"]')
                    ->waitForLocation('/backstage/file-types')
                    ->assertSee('File Types')
                    ->assertSee('Add File Type');

            // Test Settings
            $browser->click('a[href="' . route('backstage.settings.index') . '"]')
                    ->waitForLocation('/backstage/settings')
                    ->assertSee('System Settings')
                    ->assertSee('General Settings');
        });
    }

    public function test_admin_can_create_backstage_user()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/users')
                    ->click('a[href="' . route('backstage.users.create') . '"]')
                    ->waitForLocation('/backstage/users/create')
                    ->assertSee('Create Backstage User')
                    ->type('name', 'New Admin')
                    ->type('email', 'newadmin@example.com')
                    ->type('password', 'password123')
                    ->type('password_confirmation', 'password123')
                    ->check('is_admin')
                    ->press('Create User')
                    ->waitForLocation('/backstage/users')
                    ->assertSee('New Admin')
                    ->assertSee('newadmin@example.com');
        });
    }

    public function test_admin_can_manage_quickdrop_users()
    {
        // Create some test data
        $quickDropUser = QuickDropUser::factory()->create([
            'name' => 'Test QuickDrop User',
            'email' => 'quickdrop@example.com'
        ]);

        $this->browse(function (Browser $browser) use ($quickDropUser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/quickdrop-users')
                    ->assertSee('Test QuickDrop User')
                    ->assertSee('quickdrop@example.com')
                    ->click('a[href="' . route('backstage.quickdrop-users.show', $quickDropUser) . '"]')
                    ->waitForText('User Details')
                    ->assertSee('Test QuickDrop User')
                    ->assertSee('Storage Usage')
                    ->assertSee('Account Status');
        });
    }

    public function test_admin_can_view_quickdrop_details()
    {
        // Create test data
        $user = QuickDropUser::factory()->create();
        $uploadRequest = UploadRequest::factory()->create([
            'title' => 'Test QuickDrop',
            'quickdrop_user_id' => $user->id,
            'reference_number' => 'TEST123'
        ]);
        
        // Create upload objects and attach them to the request
        $uploadObjects = UploadObject::factory()->count(3)->create([
            'quickdrop_owner_id' => $user->id
        ]);
        
        // Attach objects to request via pivot table
        foreach ($uploadObjects as $object) {
            $uploadRequest->uploadObjects()->attach($object->id);
        }

        $this->browse(function (Browser $browser) use ($uploadRequest) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/quickdrops')
                    ->assertSee('Test QuickDrop')
                    ->assertSee('TEST123')
                    ->click('a[href="' . route('backstage.quickdrops.show', $uploadRequest->unique_request_id) . '"]')
                    ->waitForText('QuickDrop Details')
                    ->assertSee('Test QuickDrop')
                    ->assertSee('Files (3)')
                    ->assertSee('Download History');
        });
    }

    public function test_admin_can_manage_file_types()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/file-types')
                    ->assertSee('File Types')
                    ->assertPresent('input[type="search"]')
                    ->assertSee('Images')
                    ->assertSee('Documents')
                    ->assertSee('Videos');

            // Try to create a new file type
            $browser->click('a[href="' . route('backstage.file-types.create') . '"]')
                    ->waitForLocation('/backstage/file-types/create')
                    ->assertSee('Add File Type')
                    ->type('extension', 'test')
                    ->type('mime_type', 'application/test')
                    ->type('description', 'Test File Type')
                    ->select('category', 'other')
                    ->type('max_size', '10')
                    ->press('Create File Type')
                    ->waitForLocation('/backstage/file-types')
                    ->assertSee('Test File Type');
        });
    }

    public function test_admin_can_update_system_settings()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/settings')
                    ->assertSee('System Settings')
                    ->assertSee('Application Name')
                    ->assertSee('Support Email')
                    ->type('input[name="app_name"]', 'My QuickDrop')
                    ->type('input[name="support_email"]', 'support@myquickdrop.com')
                    ->press('Save General Settings')
                    ->waitForText('Settings updated successfully')
                    ->assertInputValue('input[name="app_name"]', 'My QuickDrop')
                    ->assertInputValue('input[name="support_email"]', 'support@myquickdrop.com');
        });
    }

    public function test_admin_can_view_audit_log()
    {
        // Generate some audit log entries by performing actions
        $quickDropUser = QuickDropUser::factory()->create();
        
        $this->browse(function (Browser $browser) use ($quickDropUser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/audit-log')
                    ->assertSee('Audit Log')
                    ->assertSee('Event Type')
                    ->assertSee('User')
                    ->assertSee('IP Address');

            // Perform an action to generate audit log
            $browser->visit('/backstage/quickdrop-users/' . $quickDropUser->id . '/edit')
                    ->type('name', 'Updated Name')
                    ->press('Update User')
                    ->visit('/backstage/audit-log')
                    ->assertSee('update')
                    ->assertSee('quickdrop_user');
        });
    }

    public function test_non_admin_cannot_access_backstage()
    {
        // Create a regular user (not admin)
        $regularUser = User::factory()->create([
            'is_admin' => false
        ]);

        $this->browse(function (Browser $browser) use ($regularUser) {
            $browser->loginAs($regularUser)
                    ->visit('/backstage/dashboard')
                    ->assertUrlIs(url('/'))
                    ->assertDontSee('Backstage');
        });
    }

    public function test_admin_can_extend_quickdrop_expiry()
    {
        $user = QuickDropUser::factory()->create();
        $uploadRequest = UploadRequest::factory()->create([
            'title' => 'Expiring QuickDrop',
            'quickdrop_user_id' => $user->id,
            'expires_at' => now()->addDay()
        ]);

        $this->browse(function (Browser $browser) use ($uploadRequest) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/quickdrops/' . $uploadRequest->unique_request_id)
                    ->assertSee('Expiring QuickDrop')
                    ->assertSee('Extend Expiry')
                    ->press('Extend Expiry')
                    ->waitForText('Expiry extended by 7 days')
                    ->assertSee('Expiry extended');
        });
    }

    public function test_admin_can_export_data()
    {
        // Create some test data
        QuickDropUser::factory()->count(5)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/quickdrop-users')
                    ->assertSee('Export CSV')
                    ->click('button:contains("Export CSV")')
                    ->pause(1000); // Wait for download
            
            // Note: We can't directly test file downloads in Dusk,
            // but we can verify the button exists and is clickable
        });
    }

    public function test_admin_can_search_and_filter()
    {
        // Create test data with specific attributes
        $activeUser = QuickDropUser::factory()->create([
            'name' => 'Active User',
            'is_active' => true
        ]);
        
        $suspendedUser = QuickDropUser::factory()->create([
            'name' => 'Suspended User',
            'is_active' => false
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/quickdrop-users')
                    ->type('input[type="search"]', 'Active')
                    ->pause(500) // Wait for search
                    ->assertSee('Active User')
                    ->assertDontSee('Suspended User')
                    ->clear('input[type="search"]')
                    ->select('select[name="is_active"]', '0')
                    ->pause(500) // Wait for filter
                    ->assertSee('Suspended User')
                    ->assertDontSee('Active User');
        });
    }

    public function test_theme_switching_persists()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/dashboard')
                    ->click('button[aria-label*="theme"]') // Click theme toggle
                    ->pause(500)
                    ->assertAttribute('html', 'data-theme', 'dark')
                    ->refresh()
                    ->assertAttribute('html', 'data-theme', 'dark');
        });
    }
}