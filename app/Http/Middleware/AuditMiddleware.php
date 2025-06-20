<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    /**
     * Routes that should be audited
     */
    protected array $auditableRoutes = [
        // Authentication
        'quickdrop.login' => ['POST', AuditLog::EVENT_LOGIN, 'User login attempt'],
        'quickdrop.logout' => ['POST', AuditLog::EVENT_LOGOUT, 'User logged out'],
        'backstage.login' => ['POST', AuditLog::EVENT_LOGIN, 'Admin login attempt'],
        'logout' => ['POST', AuditLog::EVENT_LOGOUT, 'Admin logged out'],
        
        // QuickDrop operations
        'quickdrop.store' => ['POST', AuditLog::EVENT_CREATE, 'Created new QuickDrop'],
        'quickdrop.upload' => ['POST', AuditLog::EVENT_UPLOAD, 'Uploaded file to QuickDrop'],
        'quickdrop.share-link' => ['GET', AuditLog::EVENT_SHARE, 'Generated share link'],
        
        // Downloads
        'download.file' => ['GET', AuditLog::EVENT_DOWNLOAD, 'Downloaded file'],
        
        // Backstage operations
        'backstage.users.store' => ['POST', AuditLog::EVENT_CREATE, 'Created admin user'],
        'backstage.users.update' => ['PUT', AuditLog::EVENT_UPDATE, 'Updated admin user'],
        'backstage.users.destroy' => ['DELETE', AuditLog::EVENT_DELETE, 'Deleted admin user'],
        'backstage.quickdrop-users.store' => ['POST', AuditLog::EVENT_CREATE, 'Created QuickDrop user'],
        'backstage.quickdrop-users.update' => ['PUT', AuditLog::EVENT_UPDATE, 'Updated QuickDrop user'],
        'backstage.quickdrop-users.destroy' => ['DELETE', AuditLog::EVENT_DELETE, 'Deleted QuickDrop user'],
        'backstage.quickdrop-users.toggle-status' => ['POST', AuditLog::EVENT_UPDATE, 'Toggled user status'],
        'backstage.quickdrops.extend' => ['POST', AuditLog::EVENT_EXTEND, 'Extended QuickDrop expiry'],
        'backstage.quickdrops.destroy' => ['DELETE', AuditLog::EVENT_DELETE, 'Deleted QuickDrop'],
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only audit specific routes and methods
        $routeName = $request->route()?->getName();
        if ($routeName && isset($this->auditableRoutes[$routeName])) {
            [$method, $eventType, $description] = $this->auditableRoutes[$routeName];
            
            // Only audit if the method matches and response is successful or redirect
            if ($request->method() === $method && ($response->isSuccessful() || $response->isRedirection())) {
                $this->logRequest($request, $eventType, $description);
            }
        }
        
        return $response;
    }

    /**
     * Log the request
     */
    protected function logRequest(Request $request, string $eventType, string $description): void
    {
        try {
            $metadata = [];
            $modelType = null;
            $modelId = null;
            $category = $this->determineCategory($eventType);
            
            // Extract model information from route parameters
            if ($request->route('uploadRequest')) {
                $modelType = 'App\\Models\\UploadRequest';
                $modelId = $request->route('uploadRequest')->id ?? null;
            } elseif ($request->route('quickDropUser')) {
                $modelType = 'App\\Models\\QuickDropUser';
                $modelId = $request->route('quickDropUser')->id ?? null;
            } elseif ($request->route('user')) {
                $modelType = 'App\\Models\\User';
                $modelId = $request->route('user')->id ?? null;
            }
            
            // Add relevant metadata
            if ($request->route('unique_request_id')) {
                $metadata['unique_request_id'] = $request->route('unique_request_id');
            }
            if ($request->route('fileUuid')) {
                $metadata['file_uuid'] = $request->route('fileUuid');
            }
            
            AuditService::log(
                $eventType,
                $category,
                $description,
                $modelType,
                $modelId,
                null,
                null,
                $metadata,
                $request
            );
        } catch (\Exception $e) {
            // Don't let audit logging errors break the application
            \Log::error('Audit logging failed: ' . $e->getMessage());
        }
    }

    /**
     * Determine the category based on event type
     */
    protected function determineCategory(string $eventType): string
    {
        return match($eventType) {
            AuditLog::EVENT_LOGIN, AuditLog::EVENT_LOGOUT => AuditLog::CATEGORY_AUTH,
            AuditLog::EVENT_UPLOAD, AuditLog::EVENT_DOWNLOAD => AuditLog::CATEGORY_FILE,
            AuditLog::EVENT_CREATE, AuditLog::EVENT_UPDATE, AuditLog::EVENT_DELETE => AuditLog::CATEGORY_QUICKDROP,
            AuditLog::EVENT_SHARE, AuditLog::EVENT_EXTEND => AuditLog::CATEGORY_QUICKDROP,
            default => AuditLog::CATEGORY_SYSTEM,
        };
    }
}