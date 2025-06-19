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

// All routes are automatically in the 'web' middleware group from RouteServiceProvider

// Redirect root to QuickDrop index
Route::get('/', function () {
    if (auth()->guard('quickdrop')->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('quickdrop.login');
})->name('home');

// QuickDrop User Authentication Routes (Magic Links)
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

// FEAT-009: User Authentication - Protected routes
// QuickDrop User Authenticated routes
Route::middleware(['quickdrop.auth'])->group(function () {
    // FEAT-010: User Dashboard
    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // FEAT-011: Profile Management
    // Profile routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/email-preferences', [ProfileController::class, 'updateEmailPreferences'])->name('email-preferences');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // FEAT-001: QuickDrop Creation
    // FEAT-012: QuickDrop List View
    // FEAT-032: Quick Share Link Generation
    // FEAT-026: Share Analytics
    // QuickDrop authenticated routes
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
    
    // FEAT-027: Storage Analytics
    Route::prefix('storage-analytics')->name('storage-analytics.')->group(function () {
        Route::get('/', [StorageAnalyticsController::class, 'index'])->name('index');
    });
});

// FEAT-013: Public Upload Interface
// FEAT-002: File Upload System
// Public QuickDrop routes
Route::prefix('quickdrop')->name('quickdrop.')->middleware(['web'])->group(function () {
    Route::get('/{unique_request_id}', [QuickDropController::class, 'showQuickDrop'])
        ->where('unique_request_id', '[A-Za-z0-9\-_]+')
        ->name('show');

    Route::post('/{unique_request_id}/upload', [QuickDropController::class, 'upload'])
        ->where('unique_request_id', '[A-Za-z0-9\-_]+')
        ->name('upload');
});

// FEAT-003: File Download
// FEAT-004: Bulk Download (ZIP)
// Download routes (public but secured by request ID and file UUID)
Route::prefix('download')->name('download.')->middleware(['web'])->group(function () {
    Route::get('/{requestId}/{fileUuid?}', [DownloadController::class, 'download'])
        ->where('requestId', '[A-Za-z0-9\-_]+')
        ->where('fileUuid', '[A-Za-z0-9\-_]+|')  // Allow empty for bulk downloads
        ->name('file');
});

// FEAT-033: File Preview
// Preview routes (public but secured by request ID and file UUID)
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

// API routes for storage analytics
Route::middleware(['quickdrop.auth'])->prefix('api/storage-analytics')->name('api.storage-analytics.')->group(function () {
    Route::get('/overview', [StorageAnalyticsController::class, 'overview'])->name('overview');
    Route::get('/current', [StorageAnalyticsController::class, 'current'])->name('current');
    Route::get('/historical', [StorageAnalyticsController::class, 'historical'])->name('historical');
});

// Public API routes
Route::get('/api/file-types/config', [BackstageFileTypesController::class, 'config'])->name('api.file-types.config');
Route::get('/api/settings/public', [BackstageSettingsController::class, 'publicSettings'])->name('api.settings.public');

// Backstage/Admin routes
Route::middleware(['auth', 'backstage.auth'])->prefix('backstage')->name('backstage.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('backstage.dashboard');
    });
    
    // FEAT-021: Admin Dashboard
    Route::get('/dashboard', [BackstageDashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/stats', [BackstageDashboardController::class, 'stats'])->name('api.stats');
    
    // FEAT-022: User Management
    // Backstage Users (Administrators) Management
    Route::resource('users', BackstageUsersController::class);
    
    // QuickDrop Users Management
    Route::resource('quickdrop-users', BackstageQuickDropUsersController::class);
    Route::post('/quickdrop-users/{quickDropUser}/toggle-status', [BackstageQuickDropUsersController::class, 'toggleStatus'])
        ->name('quickdrop-users.toggle-status');
    Route::post('/quickdrop-users/{quickDropUser}/reset-storage', [BackstageQuickDropUsersController::class, 'resetStorage'])
        ->name('quickdrop-users.reset-storage');
    Route::get('/quickdrop-users/export/csv', [BackstageQuickDropUsersController::class, 'export'])
        ->name('quickdrop-users.export');
    
    // FEAT-023: QuickDrop Management
    // QuickDrops Management
    Route::resource('quickdrops', BackstageQuickDropsController::class)->only(['index', 'show', 'destroy']);
    Route::post('/quickdrops/{uploadRequest}/extend', [BackstageQuickDropsController::class, 'extend'])
        ->name('quickdrops.extend');
    
    // FEAT-025: Audit Log
    Route::get('/audit-log', [BackstageAuditLogController::class, 'index'])->name('audit-log.index');
    Route::get('/audit-log/export', [BackstageAuditLogController::class, 'export'])->name('audit-log.export');
    Route::get('/api/audit-log/stats', [BackstageAuditLogController::class, 'stats'])->name('api.audit-log.stats');
    
    // FEAT-024: System Settings
    Route::get('/settings', [BackstageSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [BackstageSettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/reset', [BackstageSettingsController::class, 'reset'])->name('settings.reset');
    
    // FEAT-036: Dynamic File Type Management
    Route::resource('file-types', BackstageFileTypesController::class);
    Route::post('/file-types/{fileType}/toggle', [BackstageFileTypesController::class, 'toggle'])
        ->name('file-types.toggle');
    Route::post('/file-types/bulk-toggle', [BackstageFileTypesController::class, 'bulkToggle'])
        ->name('file-types.bulk-toggle');
});

// Backstage login route
Route::get('/backstage/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('backstage.login');

// Authentication routes (for backstage administrators)
require __DIR__.'/auth.php';
