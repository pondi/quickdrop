<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FilePreviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuickDropController;
use App\Http\Controllers\BackstageDashboardController;
use App\Http\Controllers\BackstageUsersController;
use App\Http\Controllers\BackstageQuickDropUsersController;
use App\Http\Controllers\BackstageQuickDropsController;
use App\Http\Controllers\BackstageAuditLogController;
use App\Http\Controllers\QuickDropAuthController;
use App\Http\Controllers\StorageAnalyticsController;
use App\Http\Controllers\BackstageFileTypesController;
use App\Http\Controllers\BackstageSettingsController;
use Illuminate\Support\Facades\Route;

// Redirect root to QuickDrop index
Route::get('/', function () {
    if (auth()->guard('quickdrop')->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('quickdrop.login');
})->name('home');

Route::get('/login', function () {
    return redirect()->route('quickdrop.login');
});

Route::prefix('auth')->name('quickdrop.')->group(function () {
    Route::get('/login', [QuickDropAuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [QuickDropAuthController::class, 'showRegister'])->name('register');
    Route::post('/magic-link', [QuickDropAuthController::class, 'sendMagicLink'])->name('magic-link');
    Route::get('/verify/{token}', [QuickDropAuthController::class, 'verifyMagicLink'])->name('auth.verify');
    Route::post('/logout', [QuickDropAuthController::class, 'logout'])->name('logout');
    Route::post('/resend-verification', [QuickDropAuthController::class, 'resendVerification'])
        ->middleware('quickdrop.auth')
        ->name('resend-verification');
});

// Protected routes
Route::middleware(['quickdrop.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/email-preferences', [ProfileController::class, 'updateEmailPreferences'])->name('email-preferences');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('quickdrop')->name('quickdrop.')->group(function () {
        Route::get('/', [QuickDropController::class, 'index'])->name('index');
        Route::get('/create', [QuickDropController::class, 'create'])->name('create');
        Route::post('/', [QuickDropController::class, 'createQuickDrop'])->name('store');
        Route::get('/{unique_request_id}/share-link', [QuickDropController::class, 'generateShareLink'])
            ->where('unique_request_id', '[A-Za-z0-9\-_]+')
            ->name('share-link');
        Route::get('/{unique_request_id}/analytics', [QuickDropController::class, 'showAnalytics'])
            ->where('unique_request_id', '[A-Za-z0-9\-_]+')
            ->name('analytics');
        Route::get('/{unique_request_id}/api/analytics', [QuickDropController::class, 'analytics'])
            ->where('unique_request_id', '[A-Za-z0-9\-_]+')
            ->name('api.analytics');
    });
    
    Route::prefix('storage-analytics')->name('storage-analytics.')->group(function () {
        Route::get('/', [StorageAnalyticsController::class, 'index'])->name('index');
    });
});

// Public QuickDrop routes
Route::prefix('quickdrop')->name('quickdrop.')->middleware(['web'])->group(function () {
    Route::get('/{unique_request_id}', [QuickDropController::class, 'showQuickDrop'])
        ->where('unique_request_id', '[A-Za-z0-9\-_]+')
        ->name('show');
    
    Route::post('/{unique_request_id}/verify', [QuickDropController::class, 'verifyReferenceNumber'])
        ->where('unique_request_id', '[A-Za-z0-9\-_]+')
        ->name('verify');

    Route::post('/{unique_request_id}/upload', [QuickDropController::class, 'upload'])
        ->where('unique_request_id', '[A-Za-z0-9\-_]+')
        ->name('upload');
});


// Download routes
Route::prefix('download')->name('download.')->middleware(['web'])->group(function () {
    Route::get('/{request}/{file}', [DownloadController::class, 'downloadFile'])
        ->where('request', '[A-Za-z0-9\-_]+')
        ->where('file', '[0-9]+')
        ->name('file');
    
    Route::get('/{request}/all', [DownloadController::class, 'downloadAll'])
        ->where('request', '[A-Za-z0-9\-_]+')
        ->name('all');
});

// Preview routes
Route::prefix('preview')->name('preview.')->middleware(['web'])->group(function () {
    Route::get('/{requestId}/{fileUuid}', [FilePreviewController::class, 'preview'])
        ->where('requestId', '[A-Za-z0-9\-_]+')
        ->where('fileUuid', '[A-Za-z0-9\-_]+')
        ->name('file');
    
    Route::get('/{requestId}/{fileUuid}/thumbnail', [FilePreviewController::class, 'thumbnail'])
        ->where('requestId', '[A-Za-z0-9\-_]+')
        ->where('fileUuid', '[A-Za-z0-9\-_]+')
        ->name('thumbnail');
});

Route::get('/preview/{request}/{file}', [FilePreviewController::class, 'previewById'])
    ->where('request', '[A-Za-z0-9\-_]+')
    ->where('file', '[0-9]+')
    ->name('file.preview');

// API routes for storage analytics
Route::middleware(['quickdrop.auth'])->prefix('api/storage-analytics')->name('api.storage-analytics.')->group(function () {
    Route::get('/overview', [StorageAnalyticsController::class, 'overview'])->name('overview');
    Route::get('/current', [StorageAnalyticsController::class, 'current'])->name('current');
    Route::get('/historical', [StorageAnalyticsController::class, 'historical'])->name('historical');
});

// Public API routes
Route::get('/api/file-types/config', [BackstageFileTypesController::class, 'config'])->name('api.file-types.config');
Route::get('/api/settings/public', [BackstageSettingsController::class, 'publicSettings'])->name('api.settings.public');

// Admin routes
Route::middleware(['auth:web', 'backstage.auth', 'audit'])->prefix('backstage')->name('backstage.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('backstage.dashboard');
    });
    
    Route::get('/dashboard', [BackstageDashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/stats', [BackstageDashboardController::class, 'stats'])->name('api.stats');
    
    Route::resource('users', BackstageUsersController::class);
    
    // QuickDrop Users Management
    Route::resource('quickdrop-users', BackstageQuickDropUsersController::class)->parameter('quickdrop-users', 'quickDropUser');
    Route::post('/quickdrop-users/{quickDropUser}/toggle-status', [BackstageQuickDropUsersController::class, 'toggleStatus'])
        ->name('quickdrop-users.toggle-status');
    Route::post('/quickdrop-users/{quickDropUser}/reset-storage', [BackstageQuickDropUsersController::class, 'resetStorage'])
        ->name('quickdrop-users.reset-storage');
    Route::post('/quickdrop-users/{quickDropUser}/suspend', [BackstageQuickDropUsersController::class, 'suspend'])
        ->name('quickdrop-users.suspend');
    Route::get('/quickdrop-users/export/csv', [BackstageQuickDropUsersController::class, 'export'])
        ->name('quickdrop-users.export');
    Route::post('/quickdrop-users/bulk-delete', [BackstageQuickDropUsersController::class, 'bulkDelete'])
        ->name('quickdrop-users.bulk-delete');
    
    // Put specific routes before resource route to avoid conflicts
    Route::get('/quickdrops/export', [BackstageQuickDropsController::class, 'export'])
        ->name('quickdrops.export');
    Route::get('/quickdrops/storage-by-user', [BackstageQuickDropsController::class, 'storageByUser'])
        ->name('quickdrops.storage-by-user');
    Route::post('/quickdrops/bulk-delete-expired', [BackstageQuickDropsController::class, 'bulkDeleteExpired'])
        ->name('quickdrops.bulk-delete-expired');
    
    Route::resource('quickdrops', BackstageQuickDropsController::class)->only(['index', 'show', 'destroy']);
    Route::post('/quickdrops/{quickdrop}/extend', [BackstageQuickDropsController::class, 'extend'])
        ->name('quickdrops.extend');
    Route::post('/quickdrops/{quickdrop}/deactivate', [BackstageQuickDropsController::class, 'deactivate'])
        ->name('quickdrops.deactivate');
    Route::post('/quickdrops/{quickdrop}/activate', [BackstageQuickDropsController::class, 'activate'])
        ->name('quickdrops.activate');
    Route::get('/quickdrops/{quickdrop}/analytics', [BackstageQuickDropsController::class, 'analytics'])
        ->name('quickdrops.analytics');
    Route::post('/quickdrops/{quickdrop}/regenerate-link', [BackstageQuickDropsController::class, 'regenerateLink'])
        ->name('quickdrops.regenerate-link');
    
    Route::get('/audit-log', [BackstageAuditLogController::class, 'index'])->name('audit-log.index');
    Route::get('/audit-log/export', [BackstageAuditLogController::class, 'export'])->name('audit-log.export');
    Route::get('/api/audit-log/stats', [BackstageAuditLogController::class, 'stats'])->name('api.audit-log.stats');
    
    Route::get('/settings', [BackstageSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [BackstageSettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/email', [BackstageSettingsController::class, 'updateEmail'])->name('settings.email');
    Route::put('/settings/storage', [BackstageSettingsController::class, 'updateStorage'])->name('settings.storage');
    Route::put('/settings/security', [BackstageSettingsController::class, 'updateSecurity'])->name('settings.security');
    Route::get('/settings/export', [BackstageSettingsController::class, 'export'])->name('settings.export');
    Route::post('/settings/import', [BackstageSettingsController::class, 'import'])->name('settings.import');
    Route::post('/settings/reset', [BackstageSettingsController::class, 'reset'])->name('settings.reset');
    
    Route::resource('file-types', BackstageFileTypesController::class);
    Route::post('/file-types/{fileType}/toggle', [BackstageFileTypesController::class, 'toggle'])
        ->name('file-types.toggle');
    Route::post('/file-types/bulk-toggle', [BackstageFileTypesController::class, 'bulkToggle'])
        ->name('file-types.bulk-toggle');
    Route::post('/file-types/bulk-update', [BackstageFileTypesController::class, 'bulkUpdate'])
        ->name('file-types.bulk-update');
});

// Admin login routes
Route::middleware('guest')->group(function () {
    Route::get('/backstage/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])
        ->name('backstage.login');
    Route::post('/backstage/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store'])
        ->name('backstage.login.store');
});

// Authentication routes (for backstage administrators)
require __DIR__.'/auth.php';
