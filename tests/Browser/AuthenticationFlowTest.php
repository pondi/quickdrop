<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\User;
use App\Models\QuickDropUser;
use App\Models\MagicLink;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class AuthenticationFlowTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clean up any existing test data
        User::where('email', 'admin@example.com')->delete();
        User::where('email', 'regularuser@example.com')->delete();
        QuickDropUser::where('email', 'testuser@example.com')->delete();
    }

    public function test_root_redirects_to_quickdrop_login_when_not_authenticated()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertPathIs('/auth/login')
                    ->assertSee('Login to QuickDrop');
        });
    }

    public function test_login_route_redirects_to_auth_login_for_non_admin_users()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->assertPathIs('/auth/login')
                    ->assertSee('Login to QuickDrop');
        });
    }

    public function test_backstage_login_is_accessible_directly()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/backstage/login')
                    ->assertPathIs('/backstage/login')
                    ->assertSee('Login')
                    ->assertDontSee('Login to QuickDrop');
        });
    }

    public function test_quickdrop_user_can_request_magic_link_from_login()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/login')
                    ->type('email', 'testuser@example.com')
                    ->press('Send Magic Link')
                    ->waitForText('Magic link sent!', 10)
                    ->assertSee('Magic link sent!');
            
            // Verify user was created
            $this->assertDatabaseHas('quickdrop_users', [
                'email' => 'testuser@example.com'
            ]);
            
            // Verify magic link was created
            $this->assertDatabaseHas('magic_links', [
                'email' => 'testuser@example.com'
            ]);
        });
    }

    public function test_quickdrop_user_can_register_with_magic_link()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/register')
                    ->type('name', 'Test User')
                    ->type('email', 'newuser@example.com')
                    ->press('Send Magic Link')
                    ->waitForText('Magic link sent!', 10)
                    ->assertSee('Magic link sent!');
            
            // Verify user was created
            $user = QuickDropUser::where('email', 'newuser@example.com')->first();
            $this->assertNotNull($user);
            $this->assertEquals('Test User', $user->name);
        });
    }

    public function test_quickdrop_user_can_login_with_valid_magic_link()
    {
        // Create a user and magic link manually
        $user = QuickDropUser::create([
            'name' => 'Magic Link User',
            'email' => 'magicuser@example.com',
            'is_active' => true,
        ]);
        
        $token = bin2hex(random_bytes(32));
        $magicLink = MagicLink::create([
            'email' => 'magicuser@example.com',
            'token' => hash('sha256', $token),
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);
        
        $verifyUrl = URL::temporarySignedRoute(
            'quickdrop.auth.verify',
            Carbon::now()->addMinutes(15),
            ['token' => $token]
        );
        
        $this->browse(function (Browser $browser) use ($verifyUrl) {
            $browser->visit($verifyUrl)
                    ->waitForLocation('/dashboard', 10)
                    ->assertPathIs('/dashboard')
                    ->assertAuthenticatedAs($browser->driver, 'quickdrop');
        });
    }

    public function test_admin_can_login_through_backstage()
    {
        // Create an admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);
        
        $this->browse(function (Browser $browser) {
            $browser->visit('/backstage/login')
                    ->type('email', 'admin@example.com')
                    ->type('password', 'password')
                    ->press('Log in')
                    ->waitForLocation('/backstage/dashboard', 10)
                    ->assertPathIs('/backstage/dashboard')
                    ->assertAuthenticated();
        });
    }

    public function test_non_admin_cannot_login_through_backstage()
    {
        // Create a non-admin user
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'regularuser@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);
        
        $this->browse(function (Browser $browser) {
            $browser->visit('/backstage/login')
                    ->type('email', 'regularuser@example.com')
                    ->type('password', 'password')
                    ->press('Log in')
                    ->waitForLocation('/auth/login', 10)
                    ->assertPathIs('/auth/login')
                    ->assertSee('Please use the QuickDrop login.')
                    ->assertGuest();
        });
    }

    public function test_quickdrop_authenticated_user_redirected_from_root_to_dashboard()
    {
        // Create and login a QuickDrop user
        $user = QuickDropUser::create([
            'name' => 'Dashboard User',
            'email' => 'dashuser@example.com',
            'is_active' => true,
        ]);
        
        auth()->guard('quickdrop')->login($user);
        
        $this->browse(function (Browser $browser) use ($user) {
            // Manually set the session to simulate logged in state
            $browser->visit('/dashboard')
                    ->script("document.cookie = 'laravel_session=" . encrypt(['quickdrop_auth' => $user->id]) . "'");
            
            $browser->visit('/')
                    ->assertPathIs('/dashboard');
        });
    }

    public function test_authentication_redirects_work_correctly()
    {
        $this->browse(function (Browser $browser) {
            // Test various routes and their expected redirects
            $routes = [
                '/quickdrop' => '/auth/login', // Protected route
                '/profile' => '/auth/login',    // Protected route
                '/backstage' => '/backstage/login', // Admin route
                '/backstage/users' => '/backstage/login', // Admin route
            ];
            
            foreach ($routes as $route => $expectedRedirect) {
                $browser->visit($route)
                        ->assertUrlIs(url($expectedRedirect));
            }
        });
    }

    protected function tearDown(): void
    {
        // Clean up test data
        User::where('email', 'admin@example.com')->delete();
        User::where('email', 'regularuser@example.com')->delete();
        QuickDropUser::where('email', 'testuser@example.com')->delete();
        QuickDropUser::where('email', 'newuser@example.com')->delete();
        QuickDropUser::where('email', 'magicuser@example.com')->delete();
        QuickDropUser::where('email', 'dashuser@example.com')->delete();
        MagicLink::where('email', 'magicuser@example.com')->delete();
        
        parent::tearDown();
    }
}