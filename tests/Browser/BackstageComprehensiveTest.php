<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\QuickDropUser;
use App\Models\UploadRequest;
use App\Models\UploadObject;
use App\Models\SystemSetting;
use App\Models\FileTypeSetting;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BackstageComprehensiveTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run seeders
        $this->artisan('db:seed', ['--class' => 'SystemSettingsSeeder']);
        $this->artisan('db:seed', ['--class' => 'FileTypeSettingsSeeder']);
        
        // Create an admin user
        $this->admin = User::factory()->create([
            'is_admin' => true,
            'email' => 'admin@example.com',
            'password' => bcrypt('password')
        ]);
    }

    public function test_all_navigation_links_work_correctly()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/dashboard');

            // Test each navigation link
            $navigationTests = [
                ['link' => 'Dashboard', 'url' => '/backstage/dashboard', 'heading' => 'System Overview'],
                ['link' => 'Backstage Users', 'url' => '/backstage/users', 'heading' => 'Backstage Users'],
                ['link' => 'QuickDrop Users', 'url' => '/backstage/quickdrop-users', 'heading' => 'QuickDrop Users'],
                ['link' => 'QuickDrops', 'url' => '/backstage/quickdrops', 'heading' => 'QuickDrops'],
                ['link' => 'Audit Log', 'url' => '/backstage/audit-log', 'heading' => 'Audit Log'],
                ['link' => 'File Types', 'url' => '/backstage/file-types', 'heading' => 'File Types'],
                ['link' => 'Settings', 'url' => '/backstage/settings', 'heading' => 'System Settings'],
            ];

            foreach ($navigationTests as $test) {
                $browser->clickLink($test['link'])
                        ->waitForLocation($test['url'])
                        ->assertSee($test['heading'])
                        ->assertUrlIs(url($test['url']));
            }
        });
    }

    public function test_dashboard_displays_all_required_widgets()
    {
        // Create test data
        $quickDropUsers = QuickDropUser::factory()->count(5)->create();
        $uploadRequests = UploadRequest::factory()->count(10)->create([
            'quickdrop_user_id' => $quickDropUsers->random()->id
        ]);
        
        foreach ($uploadRequests as $request) {
            UploadObject::factory()->count(rand(1, 5))->create([
                'upload_request_id' => $request->id,
                'quickdrop_user_id' => $request->quickdrop_user_id
            ]);
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/dashboard')
                    ->assertSee('System Overview')
                    ->assertSee('Total Users')
                    ->assertSee('Total QuickDrops')
                    ->assertSee('Total Files')
                    ->assertSee('Storage Used')
                    ->assertSee('Recent Activity')
                    ->assertSee('Activity Trends')
                    ->assertSee('Download Statistics')
                    ->assertSee('Top Downloaded QuickDrops')
                    ->assertSee('Storage by User')
                    ->assertSee('File Type Distribution');
        });
    }

    public function test_user_management_crud_operations()
    {
        $this->browse(function (Browser $browser) {
            // Create
            $browser->loginAs($this->admin)
                    ->visit('/backstage/users/create')
                    ->type('name', 'Test Admin User')
                    ->type('email', 'testadmin@example.com')
                    ->type('password', 'password123')
                    ->type('password_confirmation', 'password123')
                    ->check('is_admin')
                    ->press('Create User')
                    ->waitForLocation('/backstage/users')
                    ->assertSee('Test Admin User');

            // Read
            $user = User::where('email', 'testadmin@example.com')->first();
            $browser->visit('/backstage/users/' . $user->id)
                    ->assertSee('Test Admin User')
                    ->assertSee('testadmin@example.com')
                    ->assertSee('Administrator');

            // Update
            $browser->visit('/backstage/users/' . $user->id . '/edit')
                    ->type('name', 'Updated Admin User')
                    ->press('Update User')
                    ->waitForLocation('/backstage/users')
                    ->assertSee('Updated Admin User');

            // Delete
            $browser->visit('/backstage/users/' . $user->id . '/edit')
                    ->press('Delete User')
                    ->acceptDialog()
                    ->waitForLocation('/backstage/users')
                    ->assertDontSee('Updated Admin User');
        });
    }

    public function test_quickdrop_user_management_features()
    {
        $quickDropUser = QuickDropUser::factory()->create([
            'name' => 'Test QuickDrop User',
            'email' => 'qduser@example.com',
            'is_active' => true
        ]);

        $this->browse(function (Browser $browser) use ($quickDropUser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/quickdrop-users')
                    ->assertSee('Test QuickDrop User');

            // Test status toggle
            $browser->visit('/backstage/quickdrop-users/' . $quickDropUser->id)
                    ->assertSee('Active')
                    ->press('Suspend User')
                    ->waitForText('User suspended successfully')
                    ->assertSee('Suspended');

            // Test email preferences
            $browser->visit('/backstage/quickdrop-users/' . $quickDropUser->id . '/edit')
                    ->check('email_on_upload_complete')
                    ->check('email_on_all_uploads_complete')
                    ->check('email_on_download')
                    ->press('Update User')
                    ->waitForLocation('/backstage/quickdrop-users')
                    ->assertSee('User updated successfully');

            // Test storage reset
            $browser->visit('/backstage/quickdrop-users/' . $quickDropUser->id)
                    ->press('Reset Storage')
                    ->acceptDialog()
                    ->waitForText('Storage reset successfully');
        });
    }

    public function test_quickdrop_management_features()
    {
        $user = QuickDropUser::factory()->create();
        $quickdrop = UploadRequest::factory()->create([
            'title' => 'Test QuickDrop',
            'quickdrop_user_id' => $user->id,
            'expires_at' => now()->addDays(7),
            'is_active' => true
        ]);
        
        // Create upload objects and attach them to the request
        $uploadObjects = UploadObject::factory()->count(3)->create([
            'quickdrop_owner_id' => $user->id
        ]);
        
        // Attach objects to request via pivot table
        foreach ($uploadObjects as $object) {
            $quickdrop->uploadObjects()->attach($object->id);
        }

        $this->browse(function (Browser $browser) use ($quickdrop) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/quickdrops')
                    ->assertSee('Test QuickDrop');

            // Test search
            $browser->type('input[name="search"]', 'Test')
                    ->pause(500)
                    ->assertSee('Test QuickDrop');

            // Test view details
            $browser->visit('/backstage/quickdrops/' . $quickdrop->unique_request_id)
                    ->assertSee('QuickDrop Details')
                    ->assertSee('Test QuickDrop')
                    ->assertSee('Files (3)')
                    ->assertSee('Download History');

            // Test extend expiry
            $browser->press('Extend Expiry')
                    ->waitForText('Expiry extended')
                    ->assertSee('Expiry extended');

            // Test deactivate
            $browser->press('Deactivate')
                    ->waitForText('QuickDrop deactivated')
                    ->assertSee('Inactive');
        });
    }

    public function test_audit_log_functionality()
    {
        // Generate some audit logs
        AuditLog::create([
            'event_type' => 'login',
            'event_category' => 'auth',
            'description' => 'Admin logged in',
            'user_id' => $this->admin->id,
            'user_type' => 'admin',
            'ip_address' => '127.0.0.1'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/audit-log')
                    ->assertSee('Audit Log')
                    ->assertSee('Admin logged in');

            // Test search
            $browser->type('input[name="search"]', 'login')
                    ->pause(500)
                    ->assertSee('Admin logged in');

            // Test filters
            $browser->select('select[name="event_type"]', 'login')
                    ->pause(500)
                    ->assertSee('Admin logged in');

            // Test export
            $browser->assertSee('Export')
                    ->click('button:contains("Export")')
                    ->pause(1000);
        });
    }

    public function test_file_type_management()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/file-types')
                    ->assertSee('File Types');

            // Test category filter
            $browser->click('button:contains("Images")')
                    ->pause(500)
                    ->assertSee('jpg')
                    ->assertSee('png')
                    ->assertSee('gif');

            // Test search
            $browser->type('input[name="search"]', 'pdf')
                    ->pause(500)
                    ->assertSee('pdf')
                    ->assertSee('PDF Document');

            // Test enable/disable
            $fileType = FileTypeSetting::where('extension', 'pdf')->first();
            if ($fileType) {
                $browser->visit('/backstage/file-types/' . $fileType->id . '/edit')
                        ->uncheck('is_active')
                        ->press('Update File Type')
                        ->waitForLocation('/backstage/file-types')
                        ->assertSee('File type updated successfully');
            }

            // Test bulk actions
            $browser->click('button:contains("Bulk Actions")')
                    ->click('button:contains("Enable All Images")')
                    ->waitForText('enabled successfully');
        });
    }

    public function test_system_settings_all_tabs()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/settings')
                    ->assertSee('System Settings');

            // Test General Settings
            $browser->assertSee('General Settings')
                    ->type('input[name="app_name"]', 'Updated QuickDrop')
                    ->type('input[name="support_email"]', 'newsupport@example.com')
                    ->press('Save General Settings')
                    ->waitForText('Settings updated successfully');

            // Test Storage Settings
            $browser->click('button:contains("Storage Settings")')
                    ->assertSee('Maximum Upload Size')
                    ->type('input[name="max_upload_size"]', '100')
                    ->type('input[name="default_expiry_hours"]', '48')
                    ->press('Save Storage Settings')
                    ->waitForText('Settings updated successfully');

            // Test Email Settings
            $browser->click('button:contains("Email Settings")')
                    ->assertSee('From Name')
                    ->type('input[name="mail_from_name"]', 'QuickDrop System')
                    ->press('Save Email Settings')
                    ->waitForText('Settings updated successfully');

            // Test Security Settings
            $browser->click('button:contains("Security Settings")')
                    ->assertSee('Require Reference Number')
                    ->check('require_reference_number')
                    ->press('Save Security Settings')
                    ->waitForText('Settings updated successfully');

            // Test Import/Export
            $browser->assertSee('Export Settings')
                    ->assertSee('Import Settings');
        });
    }

    public function test_responsive_design_on_mobile()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->resize(375, 812) // iPhone X size
                    ->visit('/backstage/dashboard')
                    ->assertSee('QuickDrop')
                    ->assertSee('Dashboard');

            // On mobile, navigation might be hidden in a menu
            // Test that we can still navigate
            $browser->visit('/backstage/users')
                    ->assertSee('Backstage Users')
                    ->visit('/backstage/quickdrop-users')
                    ->assertSee('QuickDrop Users');
        });
    }

    public function test_error_handling_and_validation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/users/create')
                    ->press('Create User')
                    ->waitForText('The name field is required')
                    ->assertSee('The email field is required')
                    ->assertSee('The password field is required');

            // Test duplicate email
            $browser->type('name', 'Duplicate User')
                    ->type('email', $this->admin->email)
                    ->type('password', 'password123')
                    ->type('password_confirmation', 'password123')
                    ->press('Create User')
                    ->waitForText('The email has already been taken');
        });
    }

    public function test_pagination_works_correctly()
    {
        // Create many users to test pagination
        QuickDropUser::factory()->count(30)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/quickdrop-users')
                    ->assertSee('QuickDrop Users')
                    ->assertPresent('.pagination')
                    ->click('.pagination a[rel="next"]')
                    ->pause(500)
                    ->assertQueryStringHas('page', '2');
        });
    }

    public function test_back_to_app_link_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/backstage/dashboard')
                    ->click('a:contains("Back to App")')
                    ->waitForLocation('/dashboard')
                    ->assertSee('Dashboard')
                    ->assertDontSee('Backstage');
        });
    }
}