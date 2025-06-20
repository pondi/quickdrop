<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackstageAuditLogController extends Controller
{
    /**
     * Display audit log listing
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'event_type', 'event_category', 'date_from', 'date_to', 'user_type']);
        
        $logs = AuditService::getFilteredLogs(
            search: $filters['search'] ?? null,
            eventType: $filters['event_type'] ?? null,
            eventCategory: $filters['event_category'] ?? null,
            dateFrom: $filters['date_from'] ?? null,
            dateTo: $filters['date_to'] ?? null,
            userId: null,
            userType: $filters['user_type'] ?? null,
            perPage: $request->get('per_page', 25)
        );

        // Transform logs for frontend
        $logs->through(function ($log) {
            return [
                'id' => $log->id,
                'event_type' => $log->event_type,
                'event_category' => $log->event_category,
                'description' => $log->description,
                'user_name' => $log->getUserDisplayName(),
                'user_id' => $log->user_id,
                'user_type' => $log->user_type,
                'ip_address' => $log->ip_address,
                'method' => $log->method,
                'url' => $log->url,
                'created_at' => $log->created_at,
                'metadata' => $log->metadata,
                'icon' => $log->getEventIcon(),
                'color' => $log->getEventColor(),
            ];
        });

        return Inertia::render('Backstage/AuditLog/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'eventTypes' => [
                ['value' => AuditLog::EVENT_LOGIN, 'label' => 'Login'],
                ['value' => AuditLog::EVENT_LOGOUT, 'label' => 'Logout'],
                ['value' => AuditLog::EVENT_CREATE, 'label' => 'Create'],
                ['value' => AuditLog::EVENT_UPDATE, 'label' => 'Update'],
                ['value' => AuditLog::EVENT_DELETE, 'label' => 'Delete'],
                ['value' => AuditLog::EVENT_DOWNLOAD, 'label' => 'Download'],
                ['value' => AuditLog::EVENT_UPLOAD, 'label' => 'Upload'],
                ['value' => AuditLog::EVENT_VIEW, 'label' => 'View'],
                ['value' => AuditLog::EVENT_SHARE, 'label' => 'Share'],
                ['value' => AuditLog::EVENT_EXTEND, 'label' => 'Extend'],
            ],
            'eventCategories' => [
                ['value' => AuditLog::CATEGORY_AUTH, 'label' => 'Authentication'],
                ['value' => AuditLog::CATEGORY_QUICKDROP, 'label' => 'QuickDrop'],
                ['value' => AuditLog::CATEGORY_FILE, 'label' => 'File'],
                ['value' => AuditLog::CATEGORY_USER, 'label' => 'User'],
                ['value' => AuditLog::CATEGORY_SYSTEM, 'label' => 'System'],
            ],
        ]);
    }

    /**
     * Get audit log statistics for dashboard
     */
    public function stats(Request $request)
    {
        $days = $request->get('days', 7);
        $stats = AuditService::getActivitySummary($days);
        
        return response()->json($stats);
    }

    /**
     * Export audit logs as CSV
     */
    public function export(Request $request)
    {
        $filters = $request->only(['search', 'event_type', 'event_category', 'date_from', 'date_to']);
        
        $logs = AuditLog::query();

        if ($filters['search'] ?? null) {
            $logs->where(function ($q) use ($filters) {
                $q->where('description', 'like', "%{$filters['search']}%")
                  ->orWhere('url', 'like', "%{$filters['search']}%")
                  ->orWhere('ip_address', 'like', "%{$filters['search']}%");
            });
        }

        if ($filters['event_type'] ?? null) {
            $logs->ofType($filters['event_type']);
        }

        if ($filters['event_category'] ?? null) {
            $logs->ofCategory($filters['event_category']);
        }

        if (($filters['date_from'] ?? null) && ($filters['date_to'] ?? null)) {
            $logs->dateRange($filters['date_from'], $filters['date_to']);
        }

        $logs = $logs->latest()->get();

        $csv = "ID,Event Type,Category,Description,User,IP Address,Method,URL,Created At\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $log->id,
                $log->event_type,
                $log->event_category,
                '"' . str_replace('"', '""', $log->description) . '"',
                $log->getUserDisplayName(),
                $log->ip_address,
                $log->method,
                $log->url,
                $log->created_at
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="audit-log-' . now()->format('Y-m-d') . '.csv"');
    }
}