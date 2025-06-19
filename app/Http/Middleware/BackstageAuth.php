<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BackstageAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Console uses Laravel's built-in auth system
        // All users in the 'users' table are administrators
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Verify this is an admin user (all users in 'users' table should have is_admin = true)
        // This is a safety check - in the new architecture, only admins should exist in this table
        if (!$user->is_admin) {
            abort(403, 'Access denied. This account is not configured as an administrator.');
        }

        return $next($request);
    }
}