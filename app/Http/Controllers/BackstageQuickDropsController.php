<?php

namespace App\Http\Controllers;

use App\Models\UploadRequest;
use App\Models\UploadObject;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackstageQuickDropsController extends Controller
{
    public function index(Request $request)
    {
        $query = UploadRequest::with(['quickDropUser', 'uploadObjects'])
            ->withCount('uploadObjects as files_count')
            ->withSum('uploadObjects as total_size', 'file_size');

        // Apply search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%")
                    ->orWhereHas('quickDropUser', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('uploadObjects', function ($q) use ($search) {
                        $q->where('original_name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply status filter
        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('expires_at', '>', now())
                      ->where('status', 'active');
            } elseif ($status === 'expired') {
                $query->where('expires_at', '<=', now());
            } elseif ($status === 'inactive') {
                $query->where('status', 'inactive');
            }
        }

        // Order by created_at desc by default
        $query->orderBy('created_at', 'desc');

        // Paginate
        $perPage = $request->get('per_page', 25);
        $quickdrops = $query->paginate($perPage)->withQueryString();

        // Add computed properties
        $quickdrops->through(function ($quickdrop) {
            $quickdrop->is_active = $quickdrop->expires_at > now();
            $quickdrop->public_url = $quickdrop->unique_request_id 
                ? route('quickdrop.public', ['unique_request_id' => $quickdrop->unique_request_id])
                : null;
            $quickdrop->download_count = $quickdrop->uploadObjects->sum('download_count');
            
            // Rename relationship for frontend compatibility
            if ($quickdrop->quickDropUser) {
                $quickdrop->user = [
                    'id' => $quickdrop->quickDropUser->id,
                    'name' => $quickdrop->quickDropUser->name,
                    'email' => $quickdrop->quickDropUser->email,
                ];
                unset($quickdrop->quickDropUser);
            }
            
            return $quickdrop;
        });

        return Inertia::render('Backstage/QuickDrops/Index', [
            'quickdrops' => $quickdrops,
            'filters' => $request->only(['search', 'status', 'per_page'])
        ]);
    }

    public function show(UploadRequest $uploadRequest)
    {
        $uploadRequest->load(['quickDropUser', 'uploadObjects' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        // Calculate statistics
        $stats = [
            'total_files' => $uploadRequest->uploadObjects->count(),
            'total_size' => $uploadRequest->uploadObjects->sum('file_size'),
            'total_downloads' => $uploadRequest->uploadObjects->sum('download_count'),
            'unique_files' => $uploadRequest->uploadObjects->unique('file_hash')->count(),
            'version_count' => $uploadRequest->uploadObjects->where('version', '>', 1)->count(),
        ];

        // Add computed properties
        $uploadRequest->is_active = $uploadRequest->expires_at > now();
        $uploadRequest->public_url = $uploadRequest->unique_request_id 
            ? route('quickdrop.public', ['unique_request_id' => $uploadRequest->unique_request_id])
            : null;
        
        // Group files by original file
        $fileGroups = [];
        foreach ($uploadRequest->uploadObjects as $file) {
            $key = $file->original_file_id ?? $file->id;
            if (!isset($fileGroups[$key])) {
                $fileGroups[$key] = [
                    'latest' => null,
                    'versions' => []
                ];
            }
            
            if (!$fileGroups[$key]['latest'] || $file->version > $fileGroups[$key]['latest']->version) {
                $fileGroups[$key]['latest'] = $file;
            }
            
            $fileGroups[$key]['versions'][] = $file;
        }

        // Rename relationship for frontend compatibility
        if ($uploadRequest->quickDropUser) {
            $uploadRequest->user = [
                'id' => $uploadRequest->quickDropUser->id,
                'name' => $uploadRequest->quickDropUser->name,
                'email' => $uploadRequest->quickDropUser->email,
                'storage_used' => $uploadRequest->quickDropUser->storage_used,
                'storage_limit' => $uploadRequest->quickDropUser->storage_limit,
            ];
            unset($uploadRequest->quickDropUser);
        }

        return Inertia::render('Backstage/QuickDrops/Show', [
            'quickdrop' => $uploadRequest,
            'stats' => $stats,
            'fileGroups' => array_values($fileGroups),
        ]);
    }

    public function destroy(UploadRequest $quickdrop)
    {
        try {
            // Delete all associated files from storage
            foreach ($quickdrop->uploadObjects as $file) {
                try {
                    Storage::delete($file->storage_path);
                } catch (\Exception $e) {
                    Log::warning("Failed to delete file: {$file->storage_path}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Update user's storage if authenticated
            if ($quickdrop->quickDropUser) {
                $totalSize = $quickdrop->uploadObjects->sum('file_size');
                $quickdrop->quickDropUser->decrement('storage_used', $totalSize);
            }

            // Delete the upload objects first
            foreach ($quickdrop->uploadObjects as $file) {
                $file->delete();
            }
            
            // Delete the upload request
            $quickdrop->delete();

            return redirect()->route('backstage.quickdrops.index')
                ->with('success', 'QuickDrop deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete QuickDrop', [
                'id' => $quickdrop->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete QuickDrop. Please try again.');
        }
    }

    public function extend(UploadRequest $quickdrop)
    {
        try {
            // Extend by 7 days from current expiry or now, whichever is later
            $currentExpiry = $quickdrop->expires_at;
            $newExpiry = $currentExpiry > now() ? $currentExpiry->copy()->addDays(7) : now()->addDays(7);
            
            $quickdrop->update([
                'expires_at' => $newExpiry
            ]);

            return redirect()->back()
                ->with('success', 'QuickDrop expiry extended by 7 days.');
        } catch (\Exception $e) {
            Log::error('Failed to extend QuickDrop expiry', [
                'id' => $quickdrop->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to extend QuickDrop expiry. Please try again.');
        }
    }
    
    public function deactivate(UploadRequest $quickdrop)
    {
        $quickdrop->update([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);
        
        // Log the action
        AuditService::log(
            eventType: AuditLog::EVENT_UPDATE,
            eventCategory: AuditLog::CATEGORY_QUICKDROP,
            description: "Deactivated QuickDrop: {$quickdrop->unique_request_id}",
            modelType: UploadRequest::class,
            modelId: $quickdrop->id
        );
        
        return redirect()->back()->with('success', 'QuickDrop deactivated successfully.');
    }
    
    public function activate(UploadRequest $quickdrop)
    {
        $quickdrop->update([
            'is_active' => true,
            'deactivated_at' => null,
        ]);
        
        // Log the action
        AuditService::log(
            eventType: AuditLog::EVENT_UPDATE,
            eventCategory: AuditLog::CATEGORY_QUICKDROP,
            description: "Activated QuickDrop: {$quickdrop->unique_request_id}",
            modelType: UploadRequest::class,
            modelId: $quickdrop->id
        );
        
        return redirect()->back()->with('success', 'QuickDrop activated successfully.');
    }
    
    public function analytics(UploadRequest $quickdrop)
    {
        $downloadStats = DB::table('download_logs')
            ->where('upload_request_id', $quickdrop->id)
            ->selectRaw('COUNT(*) as total_downloads')
            ->selectRaw('COUNT(DISTINCT ip_address) as unique_downloaders')
            ->first();
            
        return response()->json([
            'total_downloads' => $downloadStats->total_downloads ?? 0,
            'unique_downloaders' => $downloadStats->unique_downloaders ?? 0,
        ]);
    }
    
    public function export(Request $request)
    {
        // Force non-Inertia response by removing Inertia header
        $request->headers->remove('X-Inertia');
        
        $quickdrops = UploadRequest::query()
            ->whereBetween('created_at', [
                $request->get('date_from', now()->subWeek()),
                $request->get('date_to', now())
            ])
            ->get();
            
        $csv = "ID,Title,Reference Number,Created At,Expires At,Status,Files Count,Total Size\n";
        
        foreach ($quickdrops as $qd) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",%s,%s,%s,%d,%d\n",
                $qd->id,
                str_replace('"', '""', $qd->title ?? ''),
                str_replace('"', '""', $qd->reference_number ?? ''),
                $qd->created_at,
                $qd->expires_at,
                $qd->is_active ? 'Active' : 'Inactive',
                $qd->uploadObjects()->count(),
                $qd->uploadObjects()->sum('file_size')
            );
        }
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="quickdrops-' . now()->format('Y-m-d') . '.csv"',
            'X-Inertia' => 'false'  // Explicitly tell Inertia not to handle this
        ]);
    }
    
    public function bulkDeleteExpired()
    {
        $expiredRequests = UploadRequest::where('expires_at', '<', now())
            ->with('uploadObjects')
            ->get();
        
        $count = 0;
        
        foreach ($expiredRequests as $request) {
            try {
                // Delete files from storage
                foreach ($request->uploadObjects as $file) {
                    try {
                        Storage::delete($file->storage_path);
                        $file->delete();
                    } catch (\Exception $e) {
                        Log::warning("Failed to delete file: {$file->storage_path}");
                    }
                }
                
                // Update user storage
                if ($request->quickDropUser) {
                    $totalSize = $request->uploadObjects->sum('file_size');
                    $request->quickDropUser->decrement('storage_used', $totalSize);
                }
                
                $request->delete();
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to delete expired QuickDrop {$request->id}");
            }
        }
        
        return redirect()->back()
            ->with('message', "Deleted {$count} expired QuickDrops");
    }
    
    public function storageByUser(Request $request)
    {
        // Force non-Inertia response by removing Inertia header
        $request->headers->remove('X-Inertia');
        
        $storageData = DB::table('upload_requests')
            ->join('upload_request_upload_object', 'upload_requests.id', '=', 'upload_request_upload_object.upload_request_id')
            ->join('upload_objects', 'upload_request_upload_object.upload_object_id', '=', 'upload_objects.id')
            ->join('quickdrop_users', 'upload_requests.quickdrop_user_id', '=', 'quickdrop_users.id')
            ->select(
                'quickdrop_users.id as user_id',
                'quickdrop_users.name',
                'quickdrop_users.email',
                DB::raw('SUM(upload_objects.file_size) as total_size'),
                DB::raw('COUNT(upload_objects.id) as file_count')
            )
            ->groupBy('quickdrop_users.id', 'quickdrop_users.name', 'quickdrop_users.email')
            ->get();
            
        return response()->json($storageData, 200, [
            'X-Inertia' => 'false'  // Explicitly tell Inertia not to handle this
        ]);
    }
    
    public function regenerateLink(UploadRequest $quickdrop)
    {
        $quickdrop->update([
            'unique_request_id' => Str::uuid()->toString(),
        ]);
        
        return redirect()->back()->with('success', 'Share link regenerated successfully.');
    }
}