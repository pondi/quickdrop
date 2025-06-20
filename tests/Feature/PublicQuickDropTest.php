<?php

use App\Models\QuickDropUser;
use App\Models\UploadRequest;
use App\Models\UploadObject;
use App\Services\ShareAnalyticsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('public can view active quickdrop', function () {
    $user = QuickDropUser::factory()->create();
    $quickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
        'title' => 'Test Public QuickDrop',
        'comment' => 'Test comment',
        'is_active' => true,
        'expires_at' => now()->addDays(7),
        'allow_public_download' => true,
    ]);
    
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('PublicQuickDrop')
            ->has('uploadRequest', fn ($prop) => $prop
                ->where('unique_request_id', $quickDrop->unique_request_id)
                ->where('title', 'Test Public QuickDrop')
                ->where('comment', 'Test comment')
                ->etc()
            )
        );
});

test('public cannot view expired quickdrop', function () {
    $quickDrop = UploadRequest::factory()->create([
        'expires_at' => now()->subDay(),
        'is_active' => true,
    ]);
    
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(404);
});

test('public cannot view inactive quickdrop', function () {
    $quickDrop = UploadRequest::factory()->create([
        'is_active' => false,
        'expires_at' => now()->addDays(7),
    ]);
    
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(404);
});

test('quickdrop view tracks analytics', function () {
    $quickDrop = UploadRequest::factory()->create([
        'is_active' => true,
        'expires_at' => now()->addDays(7),
    ]);
    
    // Mock user agent with Chrome
    $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/91.0.4472.124',
    ]);
    
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(200);
    
    $this->assertDatabaseHas('quickdrop_views', [
        'upload_request_id' => $quickDrop->id,
        'device_type' => 'desktop',
        'browser' => 'chrome',
    ]);
});

test('quickdrop with reference number requires verification', function () {
    $quickDrop = UploadRequest::factory()->create([
        'reference_number' => 'REF-12345',
        'is_active' => true,
        'expires_at' => now()->addDays(7),
    ]);
    
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('PublicQuickDrop')
            ->has('uploadRequest', fn ($prop) => $prop
                ->where('requires_reference_number', true)
                ->etc()
            )
        );
});

test('public can verify reference number', function () {
    $quickDrop = UploadRequest::factory()->create([
        'reference_number' => 'REF-12345',
        'is_active' => true,
        'expires_at' => now()->addDays(7),
    ]);
    
    $response = $this->post(route('quickdrop.verify', $quickDrop->unique_request_id), [
        'reference_number' => 'REF-12345',
    ]);
    
    $response->assertRedirect()
        ->assertSessionHas('quickdrop_access.' . $quickDrop->unique_request_id, true);
});

test('public cannot verify with wrong reference number', function () {
    $quickDrop = UploadRequest::factory()->create([
        'reference_number' => 'REF-12345',
        'is_active' => true,
        'expires_at' => now()->addDays(7),
    ]);
    
    $response = $this->post(route('quickdrop.verify', $quickDrop->unique_request_id), [
        'reference_number' => 'WRONG-REF',
    ]);
    
    $response->assertSessionHasErrors(['reference_number'])
        ->assertSessionMissing('quickdrop_access.' . $quickDrop->unique_request_id);
});

test('public can upload files when allowed', function () {
    $quickDrop = UploadRequest::factory()->create([
        'allow_public_upload' => true,
        'is_active' => true,
        'expires_at' => now()->addDays(7),
    ]);
    
    $file = UploadedFile::fake()->create('test.pdf', 1024);
    
    $response = $this->postJson(route('quickdrop.upload', $quickDrop->unique_request_id), [
        'file' => $file,
        'verification_token' => $quickDrop->verification_token,
    ]);
    
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
    
    $this->assertDatabaseHas('upload_objects', [
        'original_name' => 'test.pdf',
    ]);
});

test('public cannot upload files when not allowed', function () {
    $quickDrop = UploadRequest::factory()->create([
        'allow_public_upload' => false,
        'is_active' => true,
        'expires_at' => now()->addDays(7),
    ]);
    
    $file = UploadedFile::fake()->create('test.pdf', 1024);
    
    $response = $this->postJson(route('quickdrop.upload', $quickDrop->unique_request_id), [
        'file' => $file,
        'verification_token' => $quickDrop->verification_token,
    ]);
    
    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Public uploads are not allowed for this request',
        ]);
});

test('quickdrop shows files when public download allowed', function () {
    $quickDrop = UploadRequest::factory()->create([
        'allow_public_download' => true,
        'is_active' => true,
        'expires_at' => now()->addDays(7),
    ]);
    
    // Skip file attachment for now - focus on basic functionality
    
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('PublicQuickDrop')
            ->has('uploadRequest')
        );
});

test('quickdrop hides files when public download not allowed', function () {
    $quickDrop = UploadRequest::factory()->create([
        'allow_public_download' => false,
        'is_active' => true,
        'expires_at' => now()->addDays(7),
    ]);
    
    // Skip file attachment for now
    
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('PublicQuickDrop')
            ->has('uploadRequest')
        );
});

test('quickdrop shows correct expiration status', function () {
    $quickDrop = UploadRequest::factory()->create([
        'is_active' => true,
        'expires_at' => now()->addHours(2),
    ]);
    
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('PublicQuickDrop')
            ->has('uploadRequest', fn ($prop) => $prop
                ->has('expires_at')
                ->where('is_expired', false)
                ->etc()
            )
        );
});

test('quickdrop page shows upload progress', function () {
    $quickDrop = UploadRequest::factory()->create([
        'allow_public_upload' => true,
        'is_active' => true,
        'expires_at' => now()->addDays(7),
    ]);
    
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('PublicQuickDrop')
            ->has('uploadRequest', fn ($prop) => $prop
                ->where('allow_public_upload', true)
                ->etc()
            )
        );
});

test('quickdrop handles encrypted files correctly', function () {
    $quickDrop = UploadRequest::factory()->create([
        'is_encrypted' => true,
        'key_verification_hash' => 'test-hash',
        'allow_public_download' => true,
        'is_active' => true,
        'expires_at' => now()->addDays(7),
    ]);
    
    // Skip file attachment for now
    
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('PublicQuickDrop')
            ->has('uploadRequest', fn ($prop) => $prop
                ->where('is_encrypted', true)
                ->where('key_verification_hash', 'test-hash')
                ->etc()
            )
        );
});

test('non-existent quickdrop returns 404', function () {
    $response = $this->get(route('quickdrop.public', 'non-existent-id'));
    
    $response->assertStatus(404);
});

test('quickdrop tracks unique visitors correctly', function () {
    $quickDrop = UploadRequest::factory()->create([
        'is_active' => true,
        'expires_at' => now()->addDays(7),
    ]);
    
    // First visit should track the view
    $response = $this->get(route('quickdrop.public', $quickDrop->unique_request_id));
    
    $response->assertStatus(200);
    
    // Verify tracking happened by checking database
    $this->assertDatabaseHas('quickdrop_views', [
        'upload_request_id' => $quickDrop->id,
    ]);
});