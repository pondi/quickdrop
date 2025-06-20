<?php

namespace App\Http\Controllers;

use App\Models\UploadObject;
use App\Models\UploadRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    // FEAT-010: User Dashboard - Display user statistics and recent activity
    public function index()
    {
        $user = Auth::guard('quickdrop')->user();

        // Get total uploads for the user
        $totalUploads = UploadObject::where('quickdrop_owner_id', $user->id)->count();

        // Calculate total storage used
        $storageUsed = (int) UploadObject::where('quickdrop_owner_id', $user->id)->sum('file_size');

        // Get active requests count
        $activeRequests = UploadRequest::where('quickdrop_user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->count();

        // Get monthly stats
        $monthlyStats = [
            'uploads' => UploadObject::where('quickdrop_owner_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];

        // Get recent requests with file counts
        $recentRequests = UploadRequest::where('quickdrop_user_id', $user->id)
            ->select([
                'upload_requests.*',
                DB::raw('(SELECT COUNT(*) FROM upload_request_upload_object WHERE upload_request_id = upload_requests.id) as files_count'),
            ])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'totalUploads'   => $totalUploads,
            'storageUsed'    => $storageUsed,
            'activeRequests' => $activeRequests,
            'monthlyStats'   => $monthlyStats,
            'recentRequests' => $recentRequests,
        ]);
    }
}
