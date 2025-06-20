<?php

use App\Models\QuickDropUser;
use App\Models\UploadRequest;
use App\Models\UploadObject;
use function Pest\Laravel\get;

test('authenticated user can generate share link for their quickdrop', function () {
    $user = actingAsQuickDropUser();
    
    $quickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
        'title' => 'My Shared Files',
        'is_active' => true,
        'is_encrypted' => false,
        'expires_at' => now()->addDays(7),
    ]);
    
    $response = $this->get(route('quickdrop.share-link', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertJson([
            'shareUrl' => route('quickdrop.public', $quickDrop->unique_request_id),
            'title' => 'My Shared Files',
            'expiresAt' => $quickDrop->expires_at->toISOString(),
            'isEncrypted' => false,
        ]);
});

test('user cannot generate share link for another users quickdrop', function () {
    $user = actingAsQuickDropUser();
    $otherUser = QuickDropUser::factory()->create();
    
    $quickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $otherUser->id,
    ]);
    
    $response = $this->get(route('quickdrop.share-link', $quickDrop->unique_request_id));
    
    $response->assertStatus(404);
});

test('share link includes encryption status', function () {
    $user = actingAsQuickDropUser();
    
    $quickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
        'is_encrypted' => true,
        'key_verification_hash' => 'test-hash',
    ]);
    
    $response = $this->get(route('quickdrop.share-link', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertJson([
            'isEncrypted' => true,
        ]);
});

test('share link for expired quickdrop shows expiration', function () {
    $user = actingAsQuickDropUser();
    
    $quickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
        'expires_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('quickdrop.share-link', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertJsonPath('isExpired', true);
});

test('share link includes file count', function () {
    $user = actingAsQuickDropUser();
    
    $quickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
    ]);
    
    // Skip file attachment for now - test basic share link functionality
    
    $response = $this->get(route('quickdrop.share-link', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertJsonPath('fileCount', 0);
});

test('share link respects reference number requirement', function () {
    $user = actingAsQuickDropUser();
    
    $quickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
        'reference_number' => 'REF-12345',
    ]);
    
    $response = $this->get(route('quickdrop.share-link', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertJsonPath('requiresReferenceNumber', true);
});

test('share link includes public permissions', function () {
    $user = actingAsQuickDropUser();
    
    $quickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
        'allow_public_upload' => true,
        'allow_public_download' => false,
        'allow_public_delete' => true,
    ]);
    
    $response = $this->get(route('quickdrop.share-link', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertJson([
            'permissions' => [
                'upload' => true,
                'download' => false,
                'delete' => true,
            ],
        ]);
});

test('unauthenticated user cannot generate share link', function () {
    $quickDrop = UploadRequest::factory()->create();
    
    $response = $this->get(route('quickdrop.share-link', $quickDrop->unique_request_id));
    
    $response->assertRedirect(route('quickdrop.login'));
});

test('share link works with special characters in title', function () {
    $user = actingAsQuickDropUser();
    
    $quickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
        'title' => 'Test & Demo <Files> "2024"',
    ]);
    
    $response = $this->get(route('quickdrop.share-link', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertJsonPath('title', 'Test & Demo <Files> "2024"');
});

test('share link for inactive quickdrop shows status', function () {
    $user = actingAsQuickDropUser();
    
    $quickDrop = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
        'is_active' => false,
    ]);
    
    $response = $this->get(route('quickdrop.share-link', $quickDrop->unique_request_id));
    
    $response->assertStatus(200)
        ->assertJsonPath('isActive', false);
});