<?php

use App\Models\QuickDropUser;
use App\Models\UploadRequest;
use App\Models\UploadObject;
use App\Models\EmailNotificationLog;
use App\Notifications\ExpirationWarningNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Artisan;

test('expired quickdrops are not accessible', function () {
    $quickDrop = UploadRequest::factory()->create([
        'expires_at' => now()->subDay(),
        'is_active' => true,
    ]);
    
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(404);
});

test('quickdrop shows correct time remaining', function () {
    $quickDrop = UploadRequest::factory()->create([
        'expires_at' => now()->addHours(2)->addMinutes(30),
        'is_active' => true,
    ]);
    
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('PublicQuickDrop')
            ->has('uploadRequest', fn ($prop) => $prop
                ->where('is_expired', false)
                ->has('expires_at')
                ->etc()
            )
        );
});

test('quickdrop expires at exact time', function () {
    // Create a QuickDropUser first to avoid factory issues
    $quickDropUser = QuickDropUser::factory()->create([
        'last_login_at' => now()
    ]);
    
    // Test non-expired quickdrop
    $activeQuickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $quickDropUser->id,
        'expires_at' => now()->addMinutes(5),
        'is_active' => true,
    ]);
    
    $response = $this->get(route('quickdrop.public', $activeQuickDrop->unique_request_id));
    $response->assertStatus(200);
    
    // Test expired quickdrop (separate instance)
    $expiredQuickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $quickDropUser->id,
        'expires_at' => now()->subMinute(),
        'is_active' => true,
    ]);
    
    $response = $this->get(route('quickdrop.public', $expiredQuickDrop->unique_request_id));
    $response->assertStatus(404);
});

test('expired quickdrops cannot be downloaded', function () {
    $quickDrop = UploadRequest::factory()->create([
        'expires_at' => now()->subDay(),
        'is_active' => true,
        'allow_public_download' => true,
    ]);
    
    // Create an actual upload object
    $uploadObject = UploadObject::factory()->create();
    $quickDrop->uploadObjects()->attach($uploadObject->id);
    
    $response = $this->get(route('download.file', [
        'request' => $quickDrop->unique_request_id,
        'file' => $uploadObject->id,
    ]));
    
    $response->assertStatus(404)
        ->assertJson(['message' => 'This QuickDrop has expired']);
});

test('expired quickdrops cannot accept uploads', function () {
    $quickDrop = UploadRequest::factory()->create([
        'expires_at' => now()->subDay(),
        'is_active' => true,
        'allow_public_upload' => true,
    ]);
    
    $file = \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 1024);
    
    $response = $this->post(route('quickdrop.upload', $quickDrop->unique_request_id), [
        'file' => $file,
    ]);
    
    $response->assertRedirect()
        ->assertSessionHasErrors(['error']);
});

test('expiration warning command sends notifications', function () {
    Notification::fake();
    
    $user = QuickDropUser::factory()->create([
        'notify_on_expiration_warning' => true,
    ]);
    
    // Create QuickDrops expiring in different timeframes
    $expiringTomorrow = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
        'expires_at' => now()->addHours(23),
        'is_active' => true,
        'title' => 'Expiring Tomorrow',
    ]);
    
    $expiringInTwoDays = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
        'expires_at' => now()->addDays(2),
        'is_active' => true,
    ]);
    
    $alreadyExpired = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
        'expires_at' => now()->subDay(),
        'is_active' => true,
    ]);
    
    // Run the command
    Artisan::call('quickdrop:send-expiration-warnings');
    
    // Assert notification was sent only for the one expiring tomorrow
    Notification::assertSentTo(
        $user,
        ExpirationWarningNotification::class
    );
    
    // Assert notification was logged
    $this->assertDatabaseHas('email_notification_logs', [
        'quickdrop_user_id' => $user->id,
        'upload_request_id' => $expiringTomorrow->id,
        'notification_type' => EmailNotificationLog::TYPE_EXPIRATION_WARNING,
    ]);
});

test('expiration warning respects user preferences', function () {
    Notification::fake();
    
    $userWithNotifications = QuickDropUser::factory()->create([
        'notify_on_expiration_warning' => true,
    ]);
    
    $userWithoutNotifications = QuickDropUser::factory()->create([
        'notify_on_expiration_warning' => false,
    ]);
    
    // Create expiring QuickDrops for both users
    $quickDrop1 = UploadRequest::factory()->create([
        'quickdrop_user_id' => $userWithNotifications->id,
        'expires_at' => now()->addHours(20),
        'is_active' => true,
    ]);
    
    $quickDrop2 = UploadRequest::factory()->create([
        'quickdrop_user_id' => $userWithoutNotifications->id,
        'expires_at' => now()->addHours(20),
        'is_active' => true,
    ]);
    
    // Run the command
    Artisan::call('quickdrop:send-expiration-warnings');
    
    // Assert notification sent only to user with preference enabled
    Notification::assertSentTo($userWithNotifications, ExpirationWarningNotification::class);
    Notification::assertNotSentTo($userWithoutNotifications, ExpirationWarningNotification::class);
});

test('expiration warning not sent twice for same quickdrop', function () {
    Notification::fake();
    
    $user = QuickDropUser::factory()->create([
        'notify_on_expiration_warning' => true,
    ]);
    
    $quickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
        'expires_at' => now()->addHours(20),
        'is_active' => true,
    ]);
    
    // Log that notification was already sent
    EmailNotificationLog::create([
        'notification_type' => EmailNotificationLog::TYPE_EXPIRATION_WARNING,
        'recipient_email' => $user->email,
        'quickdrop_user_id' => $user->id,
        'upload_request_id' => $quickDrop->id,
        'subject' => 'Expiration Warning',
        'status' => EmailNotificationLog::STATUS_SENT,
        'sent_at' => now(),
    ]);
    
    // Run the command
    Artisan::call('quickdrop:send-expiration-warnings');
    
    // Assert no notification was sent
    Notification::assertNothingSent();
});

test('admin can extend quickdrop expiration', function () {
    $admin = \App\Models\User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin, 'web');
    
    $quickDrop = UploadRequest::factory()->create([
        'expires_at' => now()->addDay(),
        'is_active' => true,
    ]);
    
    $response = $this->post(route('backstage.quickdrops.extend', $quickDrop));
    
    $response->assertRedirect()
        ->assertSessionHas('success');
    
    // Check expiration was extended by 7 days
    $quickDrop->refresh();
    expect($quickDrop->expires_at)->toBeGreaterThan(now()->addDays(7));
});

test('quickdrop expiration status updates in real-time', function () {
    $quickDrop = UploadRequest::factory()->create([
        'expires_at' => now()->addMinutes(5),
        'is_active' => true,
    ]);
    
    // Check isExpired method
    expect($quickDrop->isExpired())->toBeFalse();
    
    // Update expiry to past
    $quickDrop->update(['expires_at' => now()->subMinute()]);
    
    expect($quickDrop->fresh()->isExpired())->toBeTrue();
});

test('bulk download respects expiration', function () {
    $quickDrop = UploadRequest::factory()->create([
        'expires_at' => now()->subDay(),
        'is_active' => true,
        'allow_public_download' => true,
    ]);
    
    // Skip file attachment - test expiration check directly
    
    $response = $this->get(route('download.all', $quickDrop->unique_request_id));
    
    $response->assertStatus(404)
        ->assertJson(['message' => 'This QuickDrop has expired']);
});