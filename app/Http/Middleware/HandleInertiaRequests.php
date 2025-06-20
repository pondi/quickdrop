<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Determine which guard to use based on the route
        $isBackstageRoute = $request->is('backstage/*') || $request->is('backstage');
        
        if ($isBackstageRoute) {
            // For backstage routes, only use web guard
            $user = auth()->guard('web')->user();
            $userData = $user ? array_merge($user->toArray(), [
                'is_quickdrop_user' => false,
                'is_admin' => $user->is_admin ?? false,
            ]) : null;
        } else {
            // For other routes, check quickdrop guard first
            $quickDropUser = auth()->guard('quickdrop')->user();
            if ($quickDropUser) {
                $userData = array_merge($quickDropUser->toArray(), [
                    'is_quickdrop_user' => true,
                    'is_admin' => false,
                ]);
            } else {
                // Fall back to web guard if no quickdrop user
                $webUser = auth()->guard('web')->user();
                $userData = $webUser ? array_merge($webUser->toArray(), [
                    'is_quickdrop_user' => false,
                    'is_admin' => $webUser->is_admin ?? false,
                ]) : null;
            }
        }
        
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $userData,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
