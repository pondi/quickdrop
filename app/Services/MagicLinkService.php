<?php

namespace App\Services;

use App\Models\MagicLink;
use App\Models\QuickDropUser;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class MagicLinkService
{
    public function sendMagicLink(string $email, string $name = null, string $purpose = 'login'): MagicLink
    {
        if ($purpose === 'register' && $name) {
            $user = QuickDropUser::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'email_verified_at' => null,
                ]
            );
        } else {
            $user = QuickDropUser::where('email', $email)->first();
            
            if (!$user && $purpose === 'login') {
                throw new \Exception('No account found with this email address.');
            }
        }

        if ($user) {
            MagicLink::where('quickdrop_user_id', $user->id)
                ->where('purpose', $purpose)
                ->valid()
                ->update(['used_at' => now()]);

            $magicLink = MagicLink::create([
                'email' => $email,
                'quickdrop_user_id' => $user->id,
                'token' => MagicLink::generateToken(),
                'expires_at' => now()->addMinutes(15),
                'purpose' => $purpose,
            ]);

            $url = URL::temporarySignedRoute(
                'quickdrop.auth.verify',
                $magicLink->expires_at,
                ['token' => $magicLink->token]
            );

            $user->notify(new MagicLinkNotification($url, $purpose));
        } else {
            throw new \Exception('User not found.');
        }

        return $magicLink;
    }

    public function verifyAndLogin(string $token): QuickDropUser
    {
        $magicLink = MagicLink::where('token', $token)->first();

        if (!$magicLink) {
            throw new \Exception('Invalid magic link.');
        }

        if (!$magicLink->isValid()) {
            if ($magicLink->isExpired()) {
                throw new \Exception('This magic link has expired.');
            }
            if ($magicLink->isUsed()) {
                throw new \Exception('This magic link has already been used.');
            }
            throw new \Exception('This magic link is no longer valid.');
        }

        $user = $magicLink->quickDropUser;

        if (!$user) {
            throw new \Exception('No account found for this magic link.');
        }

        if (!$user->is_active) {
            throw new \Exception('Your account has been deactivated.');
        }

        $magicLink->markAsUsed();

        if ($magicLink->purpose === 'verify' && !$user->isVerified()) {
            $user->markEmailAsVerified();
        }

        $user->updateLastLogin();

        Auth::guard('quickdrop')->login($user);

        return $user;
    }

    public function resendVerification(QuickDropUser $user): MagicLink
    {
        if ($user->isVerified()) {
            throw new \Exception('Email already verified.');
        }

        return $this->sendMagicLink($user->email, $user->name, 'verify');
    }

    public function cleanupExpiredLinks(): int
    {
        return MagicLink::cleanupExpired();
    }
}