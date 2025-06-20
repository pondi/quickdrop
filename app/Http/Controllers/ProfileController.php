<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuickDropProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = auth()->guard('quickdrop')->user();
        
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status'          => session('status'),
            'preferences'     => [
                'notify_on_upload_complete' => $user->notify_on_upload_complete,
                'notify_on_download' => $user->notify_on_download,
                'notify_on_expiration_warning' => $user->notify_on_expiration_warning,
                'notify_marketing' => $user->notify_marketing,
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(QuickDropProfileUpdateRequest $request): RedirectResponse
    {
        $user = auth()->guard('quickdrop')->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's email preferences.
     */
    public function updateEmailPreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notify_on_upload_complete' => ['required', 'boolean'],
            'notify_on_download' => ['required', 'boolean'],
            'notify_on_expiration_warning' => ['required', 'boolean'],
            'notify_marketing' => ['required', 'boolean'],
        ]);

        $user = auth()->guard('quickdrop')->user();
        $user->update($validated);

        return back()->with('status', 'email-preferences-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // QuickDrop users don't have passwords, so we just need confirmation
        $request->validate([
            'confirm_deletion' => ['required', 'accepted'],
        ]);

        $user = auth()->guard('quickdrop')->user();

        Auth::guard('quickdrop')->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
