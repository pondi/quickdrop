<?php

use App\Models\User;
use App\Models\QuickDropUser;
use App\Models\UploadRequest;

beforeEach(function () {
    actingAsAdmin();
});

test('admin can view users list', function () {
    User::factory()->count(5)->create();
    
    $response = $this->get(route('backstage.users.index'));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backstage/Users/Index')
            ->has('users.data', 6) // 5 + 1 admin
        );
});

test('admin can search users', function () {
    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    
    $response = $this->get(route('backstage.users.index', ['search' => 'john']));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'John Doe')
        );
});

test('admin can view user details', function () {
    $user = User::factory()->create();
    
    $response = $this->get(route('backstage.users.show', $user));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backstage/Users/Show')
            ->where('user.id', $user->id)
        );
});

test('admin can create new admin user', function () {
    $response = $this->post(route('backstage.users.store'), [
        'name' => 'New Admin',
        'email' => 'newadmin@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'is_admin' => true,
    ]);
    
    $response->assertRedirect(); // Controller redirects to show page
    
    $this->assertDatabaseHas('users', [
        'email' => 'newadmin@example.com',
        'is_admin' => true,
    ]);
});

test('admin can update user', function () {
    $user = User::factory()->create();
    
    $response = $this->put(route('backstage.users.update', $user), [
        'name' => 'Updated Name',
        'email' => $user->email,
        'is_admin' => true,
    ]);
    
    $response->assertRedirect(); // Controller redirects to show page
    
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'is_admin' => true,
    ]);
});

test('admin can delete user', function () {
    $user = User::factory()->create();
    
    $response = $this->delete(route('backstage.users.destroy', $user));
    
    $response->assertRedirect(route('backstage.users.index'));
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('admin cannot delete themselves', function () {
    $admin = auth()->user();
    
    $response = $this->delete(route('backstage.users.destroy', $admin));
    
    $response->assertRedirect()
        ->assertSessionHasErrors(['error' => 'You cannot delete your own account.']);
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('admin can view quickdrop users list', function () {
    QuickDropUser::factory()->count(10)->create();
    
    $response = $this->get(route('backstage.quickdrop-users.index'));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backstage/QuickDropUsers/Index')
            ->has('users.data', 10)
        );
});

test('admin can filter quickdrop users by verification status', function () {
    // Clear any existing users first
    QuickDropUser::query()->delete();
    
    QuickDropUser::factory()->count(3)->create(['email_verified_at' => now()]);
    QuickDropUser::factory()->count(2)->unverified()->create();
    
    $response = $this->get(route('backstage.quickdrop-users.index', ['verified' => 'yes']));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 3)
        );
});

test('admin can view quickdrop user activity', function () {
    $user = QuickDropUser::factory()->create();
    UploadRequest::factory()->count(5)->create(['quickdrop_user_id' => $user->id]);
    
    $response = $this->get(route('backstage.quickdrop-users.show', $user));
    
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Backstage/QuickDropUsers/Show')
            ->has('user')
            ->has('stats')
        );
});

test('admin can update quickdrop user email preferences', function () {
    $user = QuickDropUser::factory()->create([
        'notify_on_upload_complete' => true,
        'notify_on_download' => true,
        'notify_on_all_uploads_complete' => false,
        'notify_on_expiration' => true,
        'notify_on_share' => false,
    ]);
    
    $response = $this->put(route('backstage.quickdrop-users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'is_active' => true,
        'email_verified' => true,
        'notify_on_upload_complete' => false,
        'notify_on_download' => false,
        'notify_on_all_uploads_complete' => true,
        'notify_on_expiration' => true,
        'notify_on_share' => true,
    ]);
    
    $response->assertRedirect();
    
    $user->refresh();
    $this->assertFalse($user->notify_on_upload_complete);
    $this->assertFalse($user->notify_on_download);
    $this->assertTrue($user->notify_on_all_uploads_complete);
    $this->assertTrue($user->notify_on_expiration);
    $this->assertTrue($user->notify_on_share);
});

test('admin can suspend quickdrop user', function () {
    $user = QuickDropUser::factory()->create();
    
    $response = $this->post(route('backstage.quickdrop-users.suspend', $user));
    
    $response->assertRedirect();
    
    // Verify all user's active uploads are deactivated
    $this->assertDatabaseMissing('upload_requests', [
        'quickdrop_user_id' => $user->id,
        'is_active' => true,
    ]);
});

test('admin can export user data', function () {
    QuickDropUser::factory()->count(5)->create();
    
    $response = $this->get(route('backstage.quickdrop-users.export', ['format' => 'csv']));
    
    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('Content-Disposition');
});

test('user creation validates unique email', function () {
    User::factory()->create(['email' => 'existing@example.com']);
    
    $response = $this->post(route('backstage.users.store'), [
        'name' => 'New User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    
    $response->assertSessionHasErrors(['email']);
});

test('admin can bulk delete inactive quickdrop users', function () {
    $activeUsers = QuickDropUser::factory()->count(3)->create();
    $inactiveUsers = QuickDropUser::factory()->count(5)->create([
        'created_at' => now()->subMonths(7),
    ]);
    
    // Mark inactive users as having no recent uploads
    foreach ($inactiveUsers as $user) {
        UploadRequest::factory()->create([
            'quickdrop_user_id' => $user->id,
            'created_at' => now()->subMonths(7),
        ]);
    }
    
    $response = $this->post(route('backstage.quickdrop-users.bulk-delete'), [
        'inactive_months' => 6,
    ]);
    
    $response->assertRedirect();
    
    // Active users should remain
    foreach ($activeUsers as $user) {
        $this->assertDatabaseHas('quickdrop_users', ['id' => $user->id]);
    }
    
    // Inactive users should be soft deleted
    foreach ($inactiveUsers as $user) {
        $this->assertSoftDeleted('quickdrop_users', ['id' => $user->id]);
    }
});