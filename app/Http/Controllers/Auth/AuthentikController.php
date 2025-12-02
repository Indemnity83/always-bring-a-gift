<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthentikController extends Controller
{
    /**
     * Redirect to Authentik for authentication
     */
    public function redirect()
    {
        return Socialite::driver('authentik')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Handle callback from Authentik
     */
    public function callback()
    {
        try {
            $authentikUser = Socialite::driver('authentik')->user();

            // Find or create user
            $user = User::updateOrCreate(
                ['email' => $authentikUser->getEmail()],
                [
                    'name' => $authentikUser->getName(),
                    'email' => $authentikUser->getEmail(),
                    'email_verified_at' => now(),
                    // Optional: store Authentik ID for reference
                    // 'authentik_id' => $authentikUser->getId(),
                ]
            );

            // Log the user in
            Auth::login($user, true);

            return redirect()->intended(route('dashboard'));
        } catch (\Exception $e) {
            logger()->error('Authentik OAuth failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Unable to authenticate with Authentik. Please try again.');
        }
    }
}
