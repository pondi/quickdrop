<?php

use App\Models\SystemSetting;
use App\Models\FileTypeSetting;
use App\Models\AuditLog;

beforeEach(function () {
    actingAsAdmin();
});

test('admin can view system settings', function () {
    SystemSetting::factory()->create(['key' => 'max_file_size', 'value' => '100']);
    SystemSetting::factory()->create(['key' => 'reference_required', 'value' => 'true']);
    
    $response = $this->get(route('backstage.settings.index'));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backstage/Settings/Index')
            ->has('settings')
        );
});

test('admin can update system settings', function () {
    SystemSetting::factory()->create(['key' => 'max_file_size', 'value' => '50']);
    SystemSetting::factory()->create(['key' => 'reference_required', 'value' => 'true']);
    SystemSetting::factory()->create(['key' => 'default_expiration_hours', 'value' => '24']);
    
    $response = $this->put(route('backstage.settings.update'), [
        'settings' => [
            'max_file_size' => 100,
            'reference_required' => false,
            'default_expiration_hours' => 48,
        ],
    ]);
    
    $response->assertRedirect()
        ->assertSessionHas('success', 'Settings updated successfully');
    
    // Check the actual values stored
    $maxFileSize = SystemSetting::where('key', 'max_file_size')->first();
    $referenceRequired = SystemSetting::where('key', 'reference_required')->first();
    
    $this->assertEquals('100', $maxFileSize->value);
    // Boolean false can be stored as '0' or 'false' string
    $this->assertTrue(in_array($referenceRequired->value, ['0', 'false']), 
        "Expected reference_required to be '0' or 'false', but got: '{$referenceRequired->value}'");
});

test('settings update creates audit log', function () {
    SystemSetting::factory()->create(['key' => 'max_file_size', 'value' => '50']);
    
    $response = $this->put(route('backstage.settings.update'), [
        'settings' => [
            'max_file_size' => 100,
        ],
    ]);
    
    $response->assertRedirect();
    
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => auth()->id(),
        'event_type' => 'settings.updated',
    ]);
    
    $auditLog = AuditLog::latest()->first();
    $this->assertArrayHasKey('changes', $auditLog->metadata);
});

test('admin can manage file type settings', function () {
    $response = $this->get(route('backstage.file-types.index'));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backstage/FileTypes/Index')
            ->has('fileTypes')
        );
});

test('admin can add allowed file type', function () {
    $response = $this->post(route('backstage.file-types.store'), [
        'extension' => 'webp',
        'mime_type' => 'image/webp',
        'display_name' => 'WebP Images',
        'max_size' => 10,
        'is_allowed' => true,
        'category' => 'image',
        'priority' => 50,
    ]);
    
    $response->assertRedirect();
    
    $this->assertDatabaseHas('file_type_settings', [
        'extension' => 'webp',
        'mime_type' => 'image/webp',
        'is_allowed' => true,
    ]);
});

test('admin can update file type settings', function () {
    $fileType = FileTypeSetting::factory()->create([
        'extension' => 'pdf',
        'max_size' => 10,
        'is_allowed' => true,
    ]);
    
    $response = $this->put(route('backstage.file-types.update', $fileType), [
        'mime_type' => 'application/pdf',
        'display_name' => 'PDF Documents',
        'category' => 'document',
        'max_size' => 25,
        'is_allowed' => false,
        'priority' => 50,
    ]);
    
    $response->assertRedirect();
    
    $fileType->refresh();
    $this->assertEquals(25, $fileType->max_size);
    $this->assertFalse($fileType->is_allowed);
});

test('admin can delete file type setting', function () {
    $fileType = FileTypeSetting::factory()->create();
    
    $response = $this->delete(route('backstage.file-types.destroy', $fileType));
    
    $response->assertRedirect();
    $this->assertDatabaseMissing('file_type_settings', ['id' => $fileType->id]);
});

test('admin can bulk update file type settings', function () {
    // Create specific file types to avoid duplicates
    $imageTypes = collect([
        FileTypeSetting::factory()->create([
            'extension' => 'jpg',
            'category' => 'image',
            'is_allowed' => true,
        ]),
        FileTypeSetting::factory()->create([
            'extension' => 'png',
            'category' => 'image',
            'is_allowed' => true,
        ]),
        FileTypeSetting::factory()->create([
            'extension' => 'gif',
            'category' => 'image',
            'is_allowed' => true,
        ]),
    ]);
    
    $response = $this->post(route('backstage.file-types.bulk-update'), [
        'category' => 'image',
        'updates' => [
            'is_allowed' => false,
        ],
    ]);
    
    $response->assertRedirect();
    
    foreach ($imageTypes as $type) {
        $this->assertDatabaseHas('file_type_settings', [
            'id' => $type->id,
            'is_allowed' => false,
        ]);
    }
});

test('admin can configure email settings', function () {
    $response = $this->put(route('backstage.settings.email'), [
        'email_settings' => [
            'from_address' => 'noreply@quickdrop.com',
            'from_name' => 'QuickDrop',
            'reply_to' => 'support@quickdrop.com',
            'notification_footer' => 'Thank you for using QuickDrop!',
        ],
    ]);
    
    $response->assertRedirect();
    
    $this->assertDatabaseHas('system_settings', [
        'key' => 'email.from_address',
        'value' => 'noreply@quickdrop.com',
    ]);
});

test('admin can configure storage settings', function () {
    $response = $this->put(route('backstage.settings.storage'), [
        'storage_settings' => [
            'disk' => 's3',
            'path_prefix' => 'quickdrop',
            'cleanup_expired_after_days' => 7,
            'max_total_storage_gb' => 1000,
        ],
    ]);
    
    $response->assertRedirect();
    
    $this->assertDatabaseHas('system_settings', [
        'key' => 'storage.disk',
        'value' => 's3',
    ]);
});

test('admin can configure security settings', function () {
    $response = $this->put(route('backstage.settings.security'), [
        'security_settings' => [
            'max_login_attempts' => 5,
            'lockout_duration_minutes' => 15,
            'require_email_verification' => true,
            'magic_link_expiry_minutes' => 15,
            'session_lifetime_minutes' => 120,
        ],
    ]);
    
    $response->assertRedirect();
    
    $this->assertDatabaseHas('system_settings', [
        'key' => 'security.max_login_attempts',
        'value' => '5',
    ]);
});

test('admin can export all settings', function () {
    SystemSetting::factory()->count(20)->create();
    
    $response = $this->get(route('backstage.settings.export'));
    
    $response->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertHeader('Content-Disposition', 'attachment; filename="quickdrop-settings.json"');
});

test('admin can import settings', function () {
    $settingsData = [
        'settings' => [
            'max_file_size' => 200,
            'reference_required' => true,
            'default_expiration_hours' => 72,
        ],
    ];
    
    $response = $this->post(route('backstage.settings.import'), [
        'settings_json' => json_encode($settingsData),
    ]);
    
    $response->assertRedirect();
    
    $this->assertDatabaseHas('system_settings', [
        'key' => 'max_file_size',
        'value' => '200',
    ]);
});

test('settings validation prevents invalid values', function () {
    $response = $this->put(route('backstage.settings.update'), [
        'settings' => [
            'max_file_size' => -1, // Invalid negative value
            'default_expiration_hours' => 'not-a-number',
        ],
    ]);
    
    $response->assertSessionHasErrors(['settings.max_file_size', 'settings.default_expiration_hours']);
});