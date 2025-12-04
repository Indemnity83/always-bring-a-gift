<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class AuthentikController extends Controller
{
    /**
     * Redirect to Authentik for authentication
     */
    public function redirect()
    {
        return Socialite::driver('authentik')
            ->stateless() // Use stateless mode to avoid session issues
            ->redirect();
    }

    /**
     * Handle callback from Authentik
     */
    public function callback()
    {
        try {
            // Use stateless mode and set timeout
            $authentikUser = Socialite::driver('authentik')
                ->stateless()
                ->setHttpClient(new \GuzzleHttp\Client([
                    'timeout' => 60, // Increase timeout to 60 seconds
                    'connect_timeout' => 10,
                ]))
                ->user();

            $email = $authentikUser->getEmail();
            $name = $authentikUser->getName();

            Log::info('Authentik OAuth successful', [
                'email' => $email,
                'name' => $name,
                'authentik_id' => $authentikUser->getId(),
                'raw_data' => $authentikUser->getRaw(),
            ]);

            // Validate required email
            if (empty($email)) {
                Log::error('Authentik did not provide email address', [
                    'name' => $name,
                    'authentik_id' => $authentikUser->getId(),
                    'raw_data' => $authentikUser->getRaw(),
                ]);

                return redirect()->route('login')
                    ->with('error', 'Authentication failed: Authentik did not provide an email address. Please check your Authentik provider configuration includes email in the property mappings.');
            }

            // Find or create user
            $user = User::firstOrNew(['email' => $email]);

            // Update user data
            $user->name = $name ?: 'Authentik User';
            $user->email = $email;
            $user->email_verified_at = now();

            // Only set password if this is a new user
            if (!$user->exists) {
                // Generate random password for OAuth users (they won't use it)
                $user->password = bcrypt(\Illuminate\Support\Str::random(32));
            }

            $user->save();

            // Log the user in
            Auth::login($user, true);

            Log::info('User logged in via Authentik', ['user_id' => $user->id]);

            return redirect()->intended(route('dashboard'));
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::error('Authentik OAuth state mismatch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Authentication session expired. Please try again.');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->hasResponse() ? $e->getResponse() : null;
            $body = $response ? $response->getBody()->getContents() : 'No response body';

            Log::error('Authentik OAuth client error', [
                'error' => $e->getMessage(),
                'status' => $response ? $response->getStatusCode() : 'N/A',
                'response_body' => $body,
            ]);

            return redirect()->route('login')
                ->with('error', 'Authentication failed. Please check your Authentik configuration.');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Authentik OAuth request timeout or connection error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Unable to connect to Authentik server. Please try again later.');
        } catch (\Exception $e) {
            Log::error('Authentik OAuth unexpected error', [
                'error' => $e->getMessage(),
                'type' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Unable to authenticate with Authentik. Please try again.');
        }
    }
}
