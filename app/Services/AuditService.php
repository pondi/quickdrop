<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log an audit event
     */
    public static function log(
        string $eventType,
        string $eventCategory,
        string $description,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?Request $request = null
    ): AuditLog {
        $request = $request ?? request();
        
        // Determine user info
        $userId = null;
        $userType = null;
        
        if (Auth::guard('web')->check()) {
            $userId = Auth::guard('web')->id();
            $userType = 'users';
        } elseif (Auth::guard('quickdrop')->check()) {
            $userId = Auth::guard('quickdrop')->id();
            $userType = 'quickdrop_users';
        }
        
        return AuditLog::create([
            'event_type' => $eventType,
            'event_category' => $eventCategory,
            'description' => $description,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'user_id' => $userId,
            'user_type' => $userType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log authentication event
     */
    public static function logAuth(string $eventType, string $description, ?array $metadata = null): AuditLog
    {
        return self::log(
            $eventType,
            AuditLog::CATEGORY_AUTH,
            $description,
            null,
            null,
            null,
            null,
            $metadata
        );
    }

    /**
     * Log QuickDrop event
     */
    public static function logQuickDrop(
        string $eventType,
        string $description,
        int $quickDropId,
        ?array $metadata = null
    ): AuditLog {
        return self::log(
            $eventType,
            AuditLog::CATEGORY_QUICKDROP,
            $description,
            'App\\Models\\UploadRequest',
            $quickDropId,
            null,
            null,
            $metadata
        );
    }

    /**
     * Log file event
     */
    public static function logFile(
        string $eventType,
        string $description,
        int $fileId,
        ?array $metadata = null
    ): AuditLog {
        return self::log(
            $eventType,
            AuditLog::CATEGORY_FILE,
            $description,
            'App\\Models\\UploadObject',
            $fileId,
            null,
            null,
            $metadata
        );
    }

    /**
     * Log user management event
     */
    public static function logUser(
        string $eventType,
        string $description,
        string $userModel,
        int $userId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): AuditLog {
        return self::log(
            $eventType,
            AuditLog::CATEGORY_USER,
            $description,
            $userModel,
            $userId,
            $oldValues,
            $newValues,
            $metadata
        );
    }

    /**
     * Log system event
     */
    public static function logSystem(string $eventType, string $description, ?array $metadata = null): AuditLog
    {
        return self::log(
            $eventType,
            AuditLog::CATEGORY_SYSTEM,
            $description,
            null,
            null,
            null,
            null,
            $metadata
        );
    }

    /**
     * Get filtered audit logs for display
     */
    public static function getFilteredLogs(
        ?string $search = null,
        ?string $eventType = null,
        ?string $eventCategory = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $userId = null,
        ?string $userType = null,
        int $perPage = 50
    ) {
        $query = AuditLog::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($eventType) {
            $query->ofType($eventType);
        }

        if ($eventCategory) {
            $query->ofCategory($eventCategory);
        }

        if ($dateFrom && $dateTo) {
            $query->dateRange($dateFrom, $dateTo);
        }

        if ($userId && $userType) {
            $query->byUser($userId, $userType);
        } elseif ($userType) {
            $query->where('user_type', $userType);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get activity summary for dashboard
     */
    public static function getActivitySummary(int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        $summary = AuditLog::where('created_at', '>=', $startDate)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        $dailyActivity = AuditLog::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return [
            'summary' => $summary,
            'daily' => $dailyActivity,
            'total' => array_sum($summary),
        ];
    }
}