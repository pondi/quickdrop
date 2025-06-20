<?php

namespace App\Http\Controllers;

use App\Models\QuickDropUser;
use App\Models\UploadRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class BackstageQuickDropUsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = QuickDropUser::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        if ($request->filled('verified')) {
            if ($request->input('verified') === 'yes') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        $users = $query->withCount(['uploadRequests', 'uploadObjects'])
                      ->orderBy($request->input('sort', 'created_at'), $request->input('order', 'desc'))
                      ->paginate(15)
                      ->withQueryString();

        return Inertia::render('Backstage/QuickDropUsers/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'verified', 'sort', 'order']),
            'stats' => [
                'total_users' => QuickDropUser::count(),
                'active_users' => QuickDropUser::where('is_active', true)->count(),
                'verified_users' => QuickDropUser::whereNotNull('email_verified_at')->count(),
                'users_this_month' => QuickDropUser::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Backstage/QuickDropUsers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:quickdrop_users'],
            'storage_limit' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'email_verified' => ['boolean'],
        ]);

        $user = QuickDropUser::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'storage_limit' => $validated['storage_limit'] ?? 5368709120, // 5GB default
            'is_active' => $validated['is_active'] ?? true,
            'email_verified_at' => $validated['email_verified'] ? now() : null,
        ]);

        return redirect()->route('console.quickdrop-users.show', $user)
            ->with('success', 'QuickDrop user created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(QuickDropUser $quickDropUser)
    {
        $quickDropUser->load(['uploadRequests' => function ($query) {
            $query->latest()->limit(10);
        }]);

        $stats = [
            'total_uploads' => $quickDropUser->uploadObjects()->count(),
            'total_requests' => $quickDropUser->uploadRequests()->count(),
            'active_requests' => $quickDropUser->uploadRequests()->where('status', 'active')->count(),
            'storage_used' => $quickDropUser->storage_used,
            'storage_limit' => $quickDropUser->storage_limit,
            'storage_percentage' => $quickDropUser->storage_used_percentage,
            'last_login' => $quickDropUser->last_login_at,
        ];

        $recentActivity = [
            'requests' => $quickDropUser->uploadRequests()->latest()->limit(5)->get(),
            'uploads' => $quickDropUser->uploadObjects()->latest()->limit(5)->get(),
        ];

        return Inertia::render('Backstage/QuickDropUsers/Show', [
            'user' => $quickDropUser,
            'stats' => $stats,
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(QuickDropUser $quickDropUser)
    {
        return Inertia::render('Backstage/QuickDropUsers/Edit', [
            'user' => $quickDropUser->only('id', 'name', 'email', 'is_active', 'storage_limit', 'email_verified_at', 
                'notify_on_upload_complete', 'notify_on_all_uploads_complete', 'notify_on_download', 
                'notify_on_expiration', 'notify_on_share'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, QuickDropUser $quickDropUser)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('quickdrop_users')->ignore($quickDropUser->id)],
            'storage_limit' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'email_verified' => ['boolean'],
            'notify_on_upload_complete' => ['boolean'],
            'notify_on_all_uploads_complete' => ['boolean'],
            'notify_on_download' => ['boolean'],
            'notify_on_expiration' => ['boolean'],
            'notify_on_share' => ['boolean'],
        ]);

        $quickDropUser->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'storage_limit' => $validated['storage_limit'] ?? $quickDropUser->storage_limit,
            'is_active' => $validated['is_active'] ?? $quickDropUser->is_active,
            'notify_on_upload_complete' => $validated['notify_on_upload_complete'] ?? $quickDropUser->notify_on_upload_complete,
            'notify_on_all_uploads_complete' => $validated['notify_on_all_uploads_complete'] ?? $quickDropUser->notify_on_all_uploads_complete,
            'notify_on_download' => $validated['notify_on_download'] ?? $quickDropUser->notify_on_download,
            'notify_on_expiration' => $validated['notify_on_expiration'] ?? $quickDropUser->notify_on_expiration,
            'notify_on_share' => $validated['notify_on_share'] ?? $quickDropUser->notify_on_share,
        ]);

        if ($validated['email_verified'] && !$quickDropUser->isVerified()) {
            $quickDropUser->markEmailAsVerified();
        } elseif (!$validated['email_verified'] && $quickDropUser->isVerified()) {
            $quickDropUser->update(['email_verified_at' => null]);
        }

        return redirect()->route('backstage.quickdrop-users.show', $quickDropUser)
            ->with('success', 'QuickDrop user updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QuickDropUser $quickDropUser)
    {
        $quickDropUser->delete();

        return redirect()->route('backstage.quickdrop-users.index')
            ->with('success', 'QuickDrop user deleted successfully.');
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(QuickDropUser $quickDropUser)
    {
        $quickDropUser->update([
            'is_active' => !$quickDropUser->is_active,
        ]);

        $status = $quickDropUser->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$status} successfully.");
    }

    /**
     * Reset user storage
     */
    public function resetStorage(QuickDropUser $quickDropUser)
    {
        $quickDropUser->update([
            'storage_used' => 0,
        ]);

        return back()->with('success', 'User storage reset successfully.');
    }

    /**
     * Export users to CSV
     */
    public function export(Request $request)
    {
        $users = QuickDropUser::select('id', 'name', 'email', 'created_at', 'last_login_at', 'storage_used', 'storage_limit', 'is_active')
            ->get();

        $csv = "ID,Name,Email,Created At,Last Login,Storage Used (MB),Storage Limit (MB),Status\n";
        
        foreach ($users as $user) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",%d,%d,%s\n",
                $user->id,
                $user->name,
                $user->email,
                $user->created_at->format('Y-m-d H:i:s'),
                $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'Never',
                round($user->storage_used / 1048576, 2),
                round($user->storage_limit / 1048576, 2),
                $user->is_active ? 'Active' : 'Inactive'
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="quickdrop-users-' . now()->format('Y-m-d') . '.csv"');
    }
    
    public function suspend(QuickDropUser $quickDropUser)
    {
        // Deactivate user
        $quickDropUser->update(['is_active' => false]);
        
        // Deactivate all user's active uploads
        UploadRequest::where('quickdrop_user_id', $quickDropUser->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'deactivated_at' => now(),
            ]);
            
        return redirect()->back()->with('success', 'User suspended successfully.');
    }
    
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'inactive_months' => 'required|integer|min:1',
        ]);
        
        $cutoffDate = now()->subMonths($request->inactive_months);
        
        // Find inactive users
        $inactiveUsers = QuickDropUser::where('created_at', '<', $cutoffDate)
            ->whereDoesntHave('uploadRequests', function ($query) use ($cutoffDate) {
                $query->where('created_at', '>=', $cutoffDate);
            })
            ->get();
            
        $count = $inactiveUsers->count();
        
        // Delete the inactive users
        foreach ($inactiveUsers as $user) {
            $user->delete();
        }
        
        return redirect()->back()->with('success', "Deleted {$count} inactive users.");
    }
}
