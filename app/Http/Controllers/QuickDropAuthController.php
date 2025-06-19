<?php

namespace App\Http\Controllers;

use App\Models\QuickDropUser;
use App\Services\MagicLinkService;
use App\Services\AuditService;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class QuickDropAuthController extends Controller
{
    protected $magicLinkService;

    public function __construct(MagicLinkService $magicLinkService)
    {
        $this->magicLinkService = $magicLinkService;
    }

    public function showLogin()
    {
        return Inertia::render('QuickDropAuth/Login');
    }

    public function showRegister()
    {
        return Inertia::render('QuickDropAuth/Register');
    }

    public function sendMagicLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required_if:purpose,register|string|max:255',
            'purpose' => 'required|in:login,register',
        ]);

        try {
            $this->magicLinkService->sendMagicLink(
                $request->email,
                $request->name,
                $request->purpose
            );

            return back()->with('success', 'Magic link sent! Please check your email.');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }
    }

    public function verifyMagicLink(Request $request, $token)
    {
        if (!$request->hasValidSignature()) {
            return redirect()->route('quickdrop.login')
                ->withErrors(['email' => 'Invalid or expired magic link.']);
        }

        try {
            $user = $this->magicLinkService->verifyAndLogin($token);
            
            // Log successful login
            AuditService::logAuth(
                AuditLog::EVENT_LOGIN,
                "QuickDrop user logged in: {$user->email}",
                ['user_id' => $user->id, 'email' => $user->email]
            );
            
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        } catch (\Exception $e) {
            return redirect()->route('quickdrop.login')
                ->withErrors(['email' => $e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('quickdrop')->user();
        
        // Log logout before actually logging out
        if ($user) {
            AuditService::logAuth(
                AuditLog::EVENT_LOGOUT,
                "QuickDrop user logged out: {$user->email}",
                ['user_id' => $user->id, 'email' => $user->email]
            );
        }
        
        Auth::guard('quickdrop')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function resendVerification(Request $request)
    {
        $user = Auth::guard('quickdrop')->user();

        if (!$user) {
            return redirect()->route('quickdrop.login');
        }

        try {
            $this->magicLinkService->resendVerification($user);
            
            return back()->with('success', 'Verification email sent!');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }
    }
}
