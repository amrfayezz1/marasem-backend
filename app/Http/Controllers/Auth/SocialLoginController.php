<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialLogin;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        \Log::info('Provider: ' . $provider);
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Find user by email
            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                return response()->json(['error' => 'No account found with this email.'], 404);
            }

            // Authenticate the user
            Auth::login($user);

            // Check if SocialLogin already exists for this user and provider
            $socialLogin = SocialLogin::where('user_id', $user->id)
                ->where('provider', $provider)
                ->first();

            if (!$socialLogin) {
                // Create a new SocialLogin record
                SocialLogin::create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            }

            return response()->json([
                'message' => 'Login successful.',
                'user' => $user,
                'token' => $user->createToken('SocialLoginApp')->plainTextToken,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to login.'], 500);
        }
    }

}
