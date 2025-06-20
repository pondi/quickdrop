<?php

namespace Tests\Browser;

use App\Models\QuickDropUser;
use App\Models\UploadRequest;
use App\Models\UploadObject;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PublicQuickDropFlowTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_public_user_can_view_and_download_files()
    {
        $quickDrop = UploadRequest::factory()->create([
            'title' => 'Public Test Files',
            'comment' => 'These are test files for public access',
            'allow_public_download' => true,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        UploadObject::factory()->count(3)->create([
            'upload_request_id' => $quickDrop->id,
        ]);

        $this->browse(function (Browser $browser) use ($quickDrop) {
            $browser->visit('/quickdrop/' . $quickDrop->unique_request_id)
                ->assertSee('Public Test Files')
                ->assertSee('These are test files for public access')
                ->assertSee('Download All')
                ->assertVisible('@file-list')
                ->assertElementCount('@file-item', 3)
                ->click('@download-all-button')
                ->waitForDownload();
        });
    }

    public function test_public_user_must_verify_reference_number()
    {
        $quickDrop = UploadRequest::factory()->create([
            'title' => 'Protected Files',
            'reference_number' => 'REF-12345',
            'allow_public_download' => true,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        $this->browse(function (Browser $browser) use ($quickDrop) {
            $browser->visit('/quickdrop/' . $quickDrop->unique_request_id)
                ->assertSee('Reference Number Required')
                ->assertVisible('@reference-input')
                ->type('@reference-input', 'WRONG-REF')
                ->press('@verify-button')
                ->waitForText('Invalid reference number')
                ->assertSee('Invalid reference number')
                ->clear('@reference-input')
                ->type('@reference-input', 'REF-12345')
                ->press('@verify-button')
                ->waitForText('Protected Files')
                ->assertSee('Protected Files')
                ->assertVisible('@file-list');
        });
    }

    public function test_public_user_can_upload_files_when_allowed()
    {
        $quickDrop = UploadRequest::factory()->create([
            'title' => 'Upload Enabled QuickDrop',
            'allow_public_upload' => true,
            'allow_public_download' => true,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        $this->browse(function (Browser $browser) use ($quickDrop) {
            $browser->visit('/quickdrop/' . $quickDrop->unique_request_id)
                ->assertSee('Upload Enabled QuickDrop')
                ->assertVisible('@upload-zone')
                ->attach('@file-input', __DIR__ . '/../fixtures/test.pdf')
                ->waitForText('test.pdf')
                ->assertSee('test.pdf')
                ->pause(1000) // Wait for upload to complete
                ->assertVisible('@file-list')
                ->assertSeeIn('@file-list', 'test.pdf');
        });
    }

    public function test_expired_quickdrop_shows_error()
    {
        $quickDrop = UploadRequest::factory()->create([
            'title' => 'Expired QuickDrop',
            'expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($quickDrop) {
            $browser->visit('/quickdrop/' . $quickDrop->unique_request_id)
                ->assertSee('404')
                ->assertSee('Page Not Found');
        });
    }

    public function test_encrypted_quickdrop_requires_key()
    {
        $quickDrop = UploadRequest::factory()->create([
            'title' => 'Encrypted Files',
            'is_encrypted' => true,
            'key_verification_hash' => hash('sha256', 'test-key'),
            'allow_public_download' => true,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        UploadObject::factory()->create([
            'upload_request_id' => $quickDrop->id,
            'is_encrypted' => true,
        ]);

        $this->browse(function (Browser $browser) use ($quickDrop) {
            $browser->visit('/quickdrop/' . $quickDrop->unique_request_id)
                ->assertSee('Encrypted Files')
                ->assertSee('This QuickDrop contains encrypted files')
                ->assertVisible('@encryption-key-input')
                ->type('@encryption-key-input', 'wrong-key')
                ->press('@unlock-button')
                ->waitForText('Invalid encryption key')
                ->clear('@encryption-key-input')
                ->type('@encryption-key-input', 'test-key')
                ->press('@unlock-button')
                ->waitUntilMissing('@encryption-modal')
                ->assertVisible('@file-list');
        });
    }

    public function test_share_link_modal_works()
    {
        $user = QuickDropUser::factory()->create();
        $quickDrop = UploadRequest::factory()->create([
            'quickdrop_user_id' => $user->id,
            'title' => 'Shareable QuickDrop',
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        $this->browse(function (Browser $browser) use ($user, $quickDrop) {
            // Login as QuickDrop user
            $browser->loginAsQuickDropUser($user)
                ->visit('/quickdrop')
                ->assertSee('Shareable QuickDrop')
                ->click('@share-button-' . $quickDrop->id)
                ->waitForText('Share Link')
                ->assertVisible('@share-modal')
                ->assertValue('@share-link-input', route('quickdrop.show', $quickDrop->unique_request_id))
                ->click('@copy-link-button')
                ->assertSee('Link copied!')
                ->click('@close-modal')
                ->assertMissing('@share-modal');
        });
    }

    public function test_quickdrop_countdown_timer_updates()
    {
        $quickDrop = UploadRequest::factory()->create([
            'title' => 'Time Limited QuickDrop',
            'is_active' => true,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->browse(function (Browser $browser) use ($quickDrop) {
            $browser->visit('/quickdrop/' . $quickDrop->unique_request_id)
                ->assertSee('Time Limited QuickDrop')
                ->assertVisible('@time-remaining')
                ->assertSeeIn('@time-remaining', '4 minutes') // Should show 4 minutes
                ->pause(60000) // Wait 1 minute
                ->assertSeeIn('@time-remaining', '3 minutes'); // Should update to 3 minutes
        });
    }

    public function test_mobile_responsive_layout()
    {
        $quickDrop = UploadRequest::factory()->create([
            'title' => 'Mobile Test',
            'allow_public_download' => true,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        UploadObject::factory()->count(5)->create([
            'upload_request_id' => $quickDrop->id,
        ]);

        $this->browse(function (Browser $browser) use ($quickDrop) {
            $browser->resize(375, 812) // iPhone X size
                ->visit('/quickdrop/' . $quickDrop->unique_request_id)
                ->assertSee('Mobile Test')
                ->assertVisible('@mobile-menu-button')
                ->click('@mobile-menu-button')
                ->assertVisible('@mobile-menu')
                ->assertSee('Download All')
                ->swipeUp('@file-list') // Test swipe gesture
                ->assertVisible('@file-item');
        });
    }
}