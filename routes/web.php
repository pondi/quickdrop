<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuickDropController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// All routes are automatically in the 'web' middleware group from RouteServiceProvider

// Redirect root to QuickDrop index
Route::get('/', function () {
    return redirect()->route('quickdrop.index');
})->name('home');

// Authenticated routes with rate limiting
Route::middleware(['auth'])->group(function () {
    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // QuickDrop authenticated routes
    Route::prefix('quickdrop')->name('quickdrop.')->group(function () {
        Route::get('/', [QuickDropController::class, 'index'])->name('index');
        Route::get('/create', [QuickDropController::class, 'create'])->name('create');
        Route::post('/', [QuickDropController::class, 'createQuickDrop'])
            ->middleware(['throttle:30,1'])
            ->name('store');
        
        // File download - authenticated only
        Route::get('/file/{unique_id}', [QuickDropController::class, 'download'])
            ->middleware(['throttle:60,1'])
            ->name('download');
    });
});

// Public QuickDrop routes
Route::prefix('quickdrop')->name('quickdrop.')->middleware(['web', 'throttle:30,1'])->group(function () {
    // View QuickDrop box
    Route::get('/{unique_request_id}', [QuickDropController::class, 'showQuickDrop'])
        ->where('unique_request_id', '[A-Za-z0-9\-]+')
        ->name('show');
    
    // File upload endpoint
    Route::post('/{unique_request_id}/upload', [QuickDropController::class, 'upload'])
        ->where('unique_request_id', '[A-Za-z0-9\-]+')
        ->name('upload');
});

// Authentication routes
require __DIR__.'/auth.php';
