<?php

uses(
    Tests\DuskTestCase::class,
    // Illuminate\Foundation\Testing\DatabaseMigrations::class,
)->in('Browser');

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Tests\TestCase as BaseTestCase;

uses(
    BaseTestCase::class,
    RefreshDatabase::class
)->in('Feature', 'Unit');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function actingAsQuickDropUser($attributes = [])
{
    $user = \App\Models\QuickDropUser::factory()->create($attributes);
    \Illuminate\Support\Facades\Auth::guard('quickdrop')->login($user);
    
    return $user;
}

function actingAsAdmin($attributes = [])
{
    $admin = \App\Models\User::factory()->create(array_merge([
        'is_admin' => true,
    ], $attributes));
    
    return test()->actingAs($admin);
}