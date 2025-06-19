<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        // This login is for backstage administrators only
        // Regular users should use the QuickDrop magic link login
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => session('status'),
            'isBackstageLogin' => true,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Get the authenticated user
        $user = Auth::user();
        
        // Log for debugging
        \Log::info('User logged in', [
            'user_id' => $user->id ?? null,
            'email' => $user->email ?? null,
            'is_admin' => $user->is_admin ?? null,
        ]);

        // Redirect backstage users to backstage dashboard
        if ($user && $user->is_admin) {
            return redirect()->route('backstage.dashboard');
        }
        
        // Non-admin users shouldn't be using this login
        Auth::guard('web')->logout();
        return redirect()->route('quickdrop.login')->with('error', 'Please use the QuickDrop login.');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
