<?php

use App\Models\QuickDropUser;

test('profile page is displayed for quickdrop users', function () {
    $user = actingAsQuickDropUser();
    
    $response = $this->get('/profile');
    
    $response->assertOk();
});

test('quickdrop user can update profile information', function () {
    $user = actingAsQuickDropUser();
    
    $response = $this->patch('/profile', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    
    $response->assertSessionHasNoErrors()
        ->assertRedirect('/profile');
    
    $user->refresh();
    
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});

test('email verification status is unchanged when email is unchanged', function () {
    $user = actingAsQuickDropUser([
        'email_verified_at' => $verifiedAt = now(),
    ]);
    
    $response = $this->patch('/profile', [
        'name' => 'Test User',
        'email' => $user->email,
    ]);
    
    $response->assertSessionHasNoErrors()
        ->assertRedirect('/profile');
    
    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('quickdrop user can update email preferences', function () {
    $user = actingAsQuickDropUser();
    
    $response = $this->put('/profile/email-preferences', [
        'notify_on_upload_complete' => false,
        'notify_on_download' => true,
        'notify_on_expiration_warning' => false,
        'notify_marketing' => false,
    ]);
    
    $response->assertRedirect();
    
    $user->refresh();
    expect($user->notify_on_upload_complete)->toBeFalse();
    expect($user->notify_on_download)->toBeTrue();
    expect($user->notify_on_expiration_warning)->toBeFalse();
    expect($user->notify_marketing)->toBeFalse();
});

test('quickdrop user can delete their account', function () {
    $user = actingAsQuickDropUser();
    
    $response = $this->delete('/profile', [
        'confirm_deletion' => true,
    ]);
    
    $response->assertRedirect('/');
    
    $this->assertGuest('quickdrop');
    $this->assertTrue($user->fresh()->trashed());
});

test('confirmation must be provided to delete account', function () {
    $user = actingAsQuickDropUser();
    
    $response = $this->delete('/profile', [
        'confirm_deletion' => false,
    ]);
    
    $response->assertSessionHasErrors('confirm_deletion');
});