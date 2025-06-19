<?php

namespace App\Http\Controllers;

use App\Models\UploadRequest;
use App\Models\UploadObject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BackstageQuickDropsController extends Controller
{
    public function index(Request $request)
    {
        $query = UploadRequest::with(['quickDropUser', 'uploadObjects'])
            ->withCount('uploadObjects as files_count')
            ->withSum('uploadObjects as total_size', 'size');

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
                        $q->where('original_filename', 'like', "%{$search}%");
                    });
            });
        }

        // Apply status filter
        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('expires_at', '>', now());
            } elseif ($status === 'expired') {
                $query->where('expires_at', '<=', now());
            }
        }

        // Order by created_at desc by default
        $query->orderBy('created_at', 'desc');

        // Paginate
        $perPage = $request->get('per_page', 10);
        $quickdrops = $query->paginate($perPage)->withQueryString();

        // Add computed properties
        $quickdrops->through(function ($quickdrop) {
            $quickdrop->is_active = $quickdrop->expires_at > now();
            $quickdrop->public_url = route('quickdrop.show', $quickdrop->unique_request_id);
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
            'total_size' => $uploadRequest->uploadObjects->sum('size'),
            'total_downloads' => $uploadRequest->uploadObjects->sum('download_count'),
            'unique_files' => $uploadRequest->uploadObjects->unique('file_hash')->count(),
            'version_count' => $uploadRequest->uploadObjects->where('version', '>', 1)->count(),
        ];

        // Add computed properties
        $uploadRequest->is_active = $uploadRequest->expires_at > now();
        $uploadRequest->public_url = route('quickdrop.show', $uploadRequest->unique_request_id);
        
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

    public function destroy(UploadRequest $uploadRequest)
    {
        try {
            // Delete all associated files from storage
            foreach ($uploadRequest->uploadObjects as $file) {
                try {
                    Storage::delete($file->stored_filename);
                } catch (\Exception $e) {
                    Log::warning("Failed to delete file: {$file->stored_filename}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Update user's storage if authenticated
            if ($uploadRequest->quickDropUser) {
                $totalSize = $uploadRequest->uploadObjects->sum('size');
                $uploadRequest->quickDropUser->decrement('storage_used', $totalSize);
            }

            // Delete the upload request (will cascade delete upload objects)
            $uploadRequest->delete();

            return redirect()->route('backstage.quickdrops.index')
                ->with('success', 'QuickDrop deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete QuickDrop', [
                'id' => $uploadRequest->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete QuickDrop. Please try again.');
        }
    }

    public function extend(UploadRequest $uploadRequest)
    {
        try {
            // Extend by 7 days from current expiry or now, whichever is later
            $currentExpiry = $uploadRequest->expires_at;
            $newExpiry = $currentExpiry > now() ? $currentExpiry->addDays(7) : now()->addDays(7);
            
            $uploadRequest->update([
                'expires_at' => $newExpiry
            ]);

            return redirect()->back()
                ->with('success', 'QuickDrop expiry extended by 7 days.');
        } catch (\Exception $e) {
            Log::error('Failed to extend QuickDrop expiry', [
                'id' => $uploadRequest->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to extend QuickDrop expiry. Please try again.');
        }
    }
}