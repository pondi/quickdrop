<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BackstageLoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_backstage_login_page_loads_without_errors()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/backstage/login')
                    ->assertSee('Sign in')
                    ->assertPresent('input[name="email"]')
                    ->assertPresent('input[name="password"]')
                    ->assertPresent('button[type="submit"]');
        });
    }

    public function test_admin_can_login_to_backstage()
    {
        // Create an admin user
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/backstage/login')
                    ->type('email', 'admin@example.com')
                    ->type('password', 'password')
                    ->press('Sign in')
                    ->waitForLocation('/backstage/dashboard')
                    ->assertSee('Dashboard')
                    ->assertSee('System Overview');
        });
    }

    public function test_non_admin_user_cannot_login_to_backstage()
    {
        // Create a non-admin user
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/backstage/login')
                    ->type('email', 'user@example.com')
                    ->type('password', 'password')
                    ->press('Sign in')
                    ->pause(1000)
                    ->assertSee('Access denied')
                    ->assertUrlIs(url('/backstage/login'));
        });
    }

    public function test_invalid_credentials_show_error()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/backstage/login')
                    ->type('email', 'nonexistent@example.com')
                    ->type('password', 'wrongpassword')
                    ->press('Sign in')
                    ->waitForText('These credentials do not match our records')
                    ->assertSee('These credentials do not match our records');
        });
    }

    public function test_backstage_login_redirects_if_already_authenticated()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/backstage/login')
                    ->assertUrlIs(url('/backstage/dashboard'));
        });
    }
}