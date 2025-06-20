<?php

use App\Models\QuickDropUser;
use App\Models\UploadRequest;
use App\Models\UploadObject;
use App\Models\DownloadLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    Storage::fake('quickdrops');
    
    // Seed system settings to avoid null errors
    $this->seed(\Database\Seeders\SystemSettingsSeeder::class);
    
    // Mock ShareAnalyticsService to avoid transaction issues
    $this->mock(\App\Services\ShareAnalyticsService::class, function ($mock) {
        $mock->shouldReceive('trackView')->andReturn(null);
        $mock->shouldReceive('trackDownload')->andReturn(null);
        $mock->shouldReceive('trackUpload')->andReturn(null);
    });
});


test('public user can view quickdrop with valid reference number', function () {
    // Create the UploadRequest first
    $uploadRequest = UploadRequest::factory()
        ->withReferenceNumber('REF-123456')
        ->create();
    
    // Verify the upload request was created
    $this->assertDatabaseHas('upload_requests', [
        'id' => $uploadRequest->id,
        'unique_request_id' => $uploadRequest->unique_request_id,
    ]);
    
    // Then create and attach UploadObjects manually
    $uploadObjects = UploadObject::factory()->count(3)->create();
    $uploadRequest->uploadObjects()->attach($uploadObjects->pluck('id'));
    
    // Refresh to get the latest data
    $uploadRequest->refresh();
    
    $response = $this->get(route('quickdrop.public', $uploadRequest->unique_request_id));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PublicQuickDrop')
            ->has('uploadRequest', fn ($prop) => $prop
                ->where('requires_reference_number', true)
                ->where('reference_number', null)
                ->etc()
            )
        );
});

test('reference number is required when configured', function () {
    $uploadRequest = UploadRequest::factory()
        ->withReferenceNumber('REF-123456')
        ->create();
    
    $response = $this->post(route('quickdrop.verify', $uploadRequest->unique_request_id), [
        'reference_number' => '',
    ]);
    
    $response->assertSessionHasErrors(['reference_number']);
});

test('incorrect reference number denies access', function () {
    $uploadRequest = UploadRequest::factory()
        ->withReferenceNumber('REF-123456')
        ->create();
    
    $response = $this->postJson(route('quickdrop.verify', $uploadRequest->unique_request_id), [
        'reference_number' => 'WRONG-REF',
    ]);
    
    $response->assertForbidden()
        ->assertJson(['message' => 'Invalid reference number']);
});

test('correct reference number grants access', function () {
    $uploadRequest = UploadRequest::factory()
        ->withReferenceNumber('REF-123456')
        ->create();
    
    $response = $this->postJson(route('quickdrop.verify', $uploadRequest->unique_request_id), [
        'reference_number' => 'REF-123456',
    ]);
    
    $response->assertOk()
        ->assertJson(['success' => true])
        ->assertSessionHas('quickdrop_access.' . $uploadRequest->unique_request_id, true);
});

test('can download single file', function () {
    $uploadRequest = UploadRequest::factory()->create();
    
    // Create actual file content
    Storage::disk('quickdrops')->put('uploads/test/document.pdf', 'PDF file content here');
    
    $uploadObject = UploadObject::factory()->create([
        'original_name' => 'document.pdf',
        'stored_name' => 'document.pdf',
        'storage_path' => 'uploads/test/document.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => strlen('PDF file content here'),
    ]);
    
    // Associate with upload request
    $uploadRequest->uploadObjects()->attach($uploadObject);
    
    $this->session(['quickdrop_access.' . $uploadRequest->unique_request_id => true]);
    
    $response = $this->get(route('download.file', [
        'request' => $uploadRequest->unique_request_id,
        'file' => $uploadObject->id,
    ]));
    
    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename=document.pdf');
    
    $this->assertDatabaseHas('upload_objects', [
        'id' => $uploadObject->id,
        'download_count' => 1,
    ]);
});

test('can download all files as zip', function () {
    $uploadRequest = UploadRequest::factory()->create();
    
    $files = collect([
        ['name' => 'doc1.pdf', 'size' => 1024, 'content' => 'PDF content 1'],
        ['name' => 'doc2.docx', 'size' => 2048, 'content' => 'DOCX content 2'],
        ['name' => 'image.jpg', 'size' => 512, 'content' => 'JPG image data'],
    ])->map(function ($fileData) use ($uploadRequest) {
        $path = 'uploads/test/' . $fileData['name'];
        Storage::disk('quickdrops')->put($path, $fileData['content']);
        
        $uploadObject = UploadObject::factory()->create([
            'original_name' => $fileData['name'],
            'stored_name' => $fileData['name'],
            'storage_path' => $path,
            'file_size' => strlen($fileData['content']),
        ]);
        
        // Associate with upload request
        $uploadRequest->uploadObjects()->attach($uploadObject);
        
        return $uploadObject;
    });
    
    $this->session(['quickdrop_access.' . $uploadRequest->unique_request_id => true]);
    
    $response = $this->get(route('download.all', $uploadRequest->unique_request_id));
    
    $response->assertOk()
        ->assertHeader('Content-Type', 'application/zip')
        ->assertHeader('Content-Disposition');
    
    $uploadRequest->refresh();
    $this->assertEquals(1, $uploadRequest->downloads_count);
});

test('download respects max downloads limit', function () {
    $uploadRequest = UploadRequest::factory()
        ->withMaxDownloads(2)
        ->downloaded(2)
        ->create();
    
    // Create actual file
    $storagePath = 'uploads/test/document.pdf';
    Storage::disk('quickdrops')->put($storagePath, 'PDF content');
    
    $uploadObject = UploadObject::factory()->create([
        'original_name' => 'document.pdf',
        'stored_name' => 'document.pdf',
        'storage_path' => $storagePath,
        'file_size' => strlen('PDF content'),
    ]);
    
    // Associate with upload request
    $uploadRequest->uploadObjects()->attach($uploadObject);
    
    $this->session(['quickdrop_access.' . $uploadRequest->unique_request_id => true]);
    
    $response = $this->get(route('download.file', [
        'request' => $uploadRequest->unique_request_id,
        'file' => $uploadObject->id,
    ]));
    
    $response->assertForbidden()
        ->assertJson(['message' => 'Maximum download limit reached']);
});

test('cannot download from expired quickdrop', function () {
    $uploadRequest = UploadRequest::factory()->expired()->create();
    
    // Create actual file
    $storagePath = 'uploads/test/document.pdf';
    Storage::disk('quickdrops')->put($storagePath, 'PDF content');
    
    $uploadObject = UploadObject::factory()->create([
        'original_name' => 'document.pdf',
        'stored_name' => 'document.pdf',
        'storage_path' => $storagePath,
        'file_size' => strlen('PDF content'),
    ]);
    
    // Associate with upload request
    $uploadRequest->uploadObjects()->attach($uploadObject);
    
    $this->session(['quickdrop_access.' . $uploadRequest->unique_request_id => true]);
    
    $response = $this->get(route('download.file', [
        'request' => $uploadRequest->unique_request_id,
        'file' => $uploadObject->id,
    ]));
    
    $response->assertNotFound()
        ->assertJson(['message' => 'This QuickDrop has expired']);
});

test('cannot download from inactive quickdrop', function () {
    $uploadRequest = UploadRequest::factory()->inactive()->create();
    
    // Create actual file
    $storagePath = 'uploads/test/document.pdf';
    Storage::disk('quickdrops')->put($storagePath, 'PDF content');
    
    $uploadObject = UploadObject::factory()->create([
        'original_name' => 'document.pdf',
        'stored_name' => 'document.pdf',
        'storage_path' => $storagePath,
        'file_size' => strlen('PDF content'),
    ]);
    
    // Associate with upload request
    $uploadRequest->uploadObjects()->attach($uploadObject);
    
    $this->session(['quickdrop_access.' . $uploadRequest->unique_request_id => true]);
    
    $response = $this->get(route('download.file', [
        'request' => $uploadRequest->unique_request_id,
        'file' => $uploadObject->id,
    ]));
    
    $response->assertNotFound()
        ->assertJson(['message' => 'This QuickDrop is no longer available']);
});

test('download without session access is denied', function () {
    $uploadRequest = UploadRequest::factory()->create();
    
    // Create actual file
    $storagePath = 'uploads/test/document.pdf';
    Storage::disk('quickdrops')->put($storagePath, 'PDF content');
    
    $uploadObject = UploadObject::factory()->create([
        'original_name' => 'document.pdf',
        'stored_name' => 'document.pdf',
        'storage_path' => $storagePath,
        'file_size' => strlen('PDF content'),
    ]);
    
    // Associate with upload request
    $uploadRequest->uploadObjects()->attach($uploadObject);
    
    $response = $this->get(route('download.file', [
        'request' => $uploadRequest->unique_request_id,
        'file' => $uploadObject->id,
    ]));
    
    $response->assertForbidden();
});

test('download logs are created', function () {
    $uploadRequest = UploadRequest::factory()->create();
    
    // Create actual file content
    Storage::disk('quickdrops')->put('uploads/test/document.pdf', 'PDF file content');
    
    $uploadObject = UploadObject::factory()->create([
        'original_name' => 'document.pdf',
        'stored_name' => 'document.pdf',
        'storage_path' => 'uploads/test/document.pdf',
        'file_size' => strlen('PDF file content'),
        'mime_type' => 'application/pdf',
    ]);
    
    // Associate with upload request
    $uploadRequest->uploadObjects()->attach($uploadObject);
    
    $this->session(['quickdrop_access.' . $uploadRequest->unique_request_id => true]);
    
    $response = $this->get(route('download.file', [
        'request' => $uploadRequest->unique_request_id,
        'file' => $uploadObject->id,
    ]));
    
    $response->assertOk();
    
    $this->assertDatabaseHas('download_logs', [
        'upload_request_id' => $uploadRequest->id,
        'upload_object_id' => $uploadObject->id,
        'ip_address' => '127.0.0.1',
    ]);
});

test('download triggers notification when enabled', function () {
    Notification::fake();
    
    // Mock the email notification service to bypass system settings check
    $this->mock(\App\Services\EmailNotificationService::class, function ($mock) {
        $mock->shouldReceive('sendDownloadAlertNotification')
            ->once()
            ->with(\Mockery::type(\App\Models\UploadObject::class), 'Anonymous');
    });
    
    $user = QuickDropUser::factory()->create(['notify_on_download' => true]);
    $uploadRequest = UploadRequest::factory()->create([
        'quickdrop_user_id' => $user->id,
    ]);
    
    // Create actual file content
    Storage::disk('quickdrops')->put('uploads/test/document.pdf', 'PDF file content');
    
    $uploadObject = UploadObject::factory()->create([
        'original_name' => 'document.pdf',
        'stored_name' => 'document.pdf',
        'storage_path' => 'uploads/test/document.pdf',
        'file_size' => strlen('PDF file content'),
        'mime_type' => 'application/pdf',
    ]);
    
    // Associate with upload request
    $uploadRequest->uploadObjects()->attach($uploadObject);
    
    $this->session(['quickdrop_access.' . $uploadRequest->unique_request_id => true]);
    
    $response = $this->get(route('download.file', [
        'request' => $uploadRequest->unique_request_id,
        'file' => $uploadObject->id,
    ]));
    
    $response->assertOk();
});

test('file preview works for supported types', function () {
    $uploadRequest = UploadRequest::factory()->create();
    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
    Storage::disk('quickdrops')->put('uploads/test/photo.jpg', $file);
    
    $uploadObject = UploadObject::factory()->create([
        'original_name' => 'photo.jpg',
        'stored_name' => 'photo.jpg',
        'storage_path' => 'uploads/test/photo.jpg',
        'mime_type' => 'image/jpeg',
    ]);
    
    // Associate with upload request
    $uploadRequest->uploadObjects()->attach($uploadObject);
    
    $this->session(['quickdrop_access.' . $uploadRequest->unique_request_id => true]);
    
    $response = $this->get(route('file.preview', [
        'request' => $uploadRequest->unique_request_id,
        'file' => $uploadObject->unique_id,
    ]));
    
    $response->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});