<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuickDropController;
use Illuminate\Support\Facades\Route;

// All routes are automatically in the 'web' middleware group from RouteServiceProvider

// Redirect root to QuickDrop index
Route::get('/', function () {
    return redirect()->route('quickdrop.index');
})->name('home');

// Authenticated routes
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
        Route::post('/', [QuickDropController::class, 'createQuickDrop'])->name('store');
    });
});

// Public QuickDrop routes
Route::prefix('quickdrop')->name('quickdrop.')->middleware(['web'])->group(function () {
    Route::get('/{unique_request_id}', [QuickDropController::class, 'showQuickDrop'])
        ->where('unique_request_id', '[A-Za-z0-9\-_]+')
        ->name('show');

    Route::post('/{unique_request_id}/upload', [QuickDropController::class, 'upload'])
        ->where('unique_request_id', '[A-Za-z0-9\-_]+')
        ->name('upload');
});

// Download routes (public but secured by request ID and file UUID)
Route::prefix('download')->name('download.')->middleware(['web'])->group(function () {
    Route::get('/{requestId}/{fileUuid?}', [DownloadController::class, 'download'])
        ->where('requestId', '[A-Za-z0-9\-_]+')
        ->where('fileUuid', '[A-Za-z0-9\-_]+|')  // Allow empty for bulk downloads
        ->name('file');
});

// Authentication routes
require __DIR__.'/auth.php';
