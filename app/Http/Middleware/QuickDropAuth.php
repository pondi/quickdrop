<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class QuickDropAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('quickdrop')->check()) {
            return redirect()->route('quickdrop.login');
        }

        $user = Auth::guard('quickdrop')->user();
        
        if (!$user->is_active) {
            Auth::guard('quickdrop')->logout();
            return redirect()->route('quickdrop.login')
                ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        return $next($request);
    }
}
