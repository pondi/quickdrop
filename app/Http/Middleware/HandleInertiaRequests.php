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
        // Check for QuickDrop user first, then console user
        $quickDropUser = auth()->guard('quickdrop')->user();
        $consoleUser = auth()->guard('web')->user();
        
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $quickDropUser ? array_merge($quickDropUser->toArray(), [
                    'is_quickdrop_user' => true,
                    'is_admin' => false,
                ]) : ($consoleUser ? array_merge($consoleUser->toArray(), [
                    'is_quickdrop_user' => false,
                    'is_admin' => $consoleUser->is_admin ?? false,
                ]) : null),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
