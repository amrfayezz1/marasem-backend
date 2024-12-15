<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialLogin;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    /**
     * @OA\Get(
     *     path="/login/{provider}/redirect",
     *     summary="Redirect to social login provider",
     *     tags={"Social Login"},
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="The social login provider (e.g., google, facebook, behance)",
     *         @OA\Schema(type="string", enum={"google", "facebook", "behance"})
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirects the user to the social provider's login page"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error in redirecting to provider"
     *     )
     * )
     */
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * @OA\Get(
     *     path="/login/{provider}/callback",
     *     summary="Handle the social login callback",
     *     tags={"Social Login"},
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="The social login provider (e.g., google, facebook, behance)",
     *         @OA\Schema(type="string", enum={"google", "facebook", "behance"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Login successful."),
     *             @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
     *             @OA\Property(property="token", type="string", example="sample-jwt-token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No account found with this email",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="No account found with this email.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unable to login"
     *     )
     * )
     */
    public function handleProviderCallback($provider)
    {
        \Log::info('Provider: ' . $provider);
        if ($provider == "behance") {
            $provider = "adobe";
        }
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
