<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client;
use App\Services\TwilioService;
use Illuminate\Support\Str;
use App\Models\PasswordResetToken;
use App\Mail\ResetPasswordOtpMail;

class LoginController extends Controller
{
    protected $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Login user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"password"},
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="john.doe@example.com"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="1234567890", description="Required if email is not provided."),
     *             @OA\Property(property="country_code", type="string", nullable=true, example="+1", description="Required if using phone for login."),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="remember", type="boolean", nullable=true, example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Login successful."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe")
     *             ),
     *             @OA\Property(property="token", type="string", example="sample-jwt-token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|string|email|max:255',
            'phone' => 'required_without:email|string',
            'country_code' => 'required_without:email|string',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
            'currency' => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->email && Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            if (isset($request->currency) && $request->currency) {
                $user->update(['preferred_currency' => $request->currency]);
            }
            $token = $user->createToken('MarasemApp')->plainTextToken;

            return response()->json([
                'message' => 'Login successful.',
                'user' => $user,
                'token' => $token,
            ]);
        } elseif ($request->phone && $request->country_code) {
            $account = User::where('phone', $request->phone)
                ->where('country_code', $request->country_code)
                ->first();

            if ($account && Auth::attempt(['email' => $account->email, 'password' => $request->password])) {
                $user = Auth::user();
                $token = $user->createToken('MarasemApp')->plainTextToken;

                return response()->json([
                    'message' => 'Login successful.',
                    'user' => $user,
                    'token' => $token,
                ]);
            }
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Logout user",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Logged out successfully.")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * @OA\Post(
     *     path="/send-otp",
     *     summary="Send OTP to email or phone",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"identifier", "type"},
     *             @OA\Property(property="identifier", type="string", example="john.doe@example.com", description="Email or phone number."),
     *             @OA\Property(property="type", type="string", enum={"email", "phone"}, example="email", description="Specify the type of identifier."),
     *             @OA\Property(property="country_code", type="string", nullable=true, example="+1", description="Required if type is phone.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="OTP sent successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No account found"
     *     )
     * )
     */
    public function sendOtp(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'country_code' => 'required_if:type,phone|string',
            'type' => 'required|string|in:email,phone',
        ]);

        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 422);
        }

        $identifier = $validated->validated()['identifier'];
        $countryCode = $request->country_code ? $validated->validated()['country_code'] : null;
        $type = $validated->validated()['type'];

        // Validate if the user exists (email or phone with country code)
        $user = null;

        if ($type === 'email') {
            $user = User::where('email', $identifier)->first();
        } elseif ($type === 'phone') {
            $user = User::where('phone', $identifier)->where('country_code', $countryCode)->first();
        }

        // If user doesn't exist, return error
        if (!$user) {
            return response()->json(['error' => 'No account found with the provided email/phone and country code.'], 404);
        }

        // Generate a 4-digit OTP
        $otp = rand(1000, 9999);
        $expiresAt = now()->addMinutes(10); // OTP expiry time

        // delete previous OTPs
        PasswordResetToken::where('email', $user->email)->delete();

        // Store OTP in database
        PasswordResetToken::create([
            'email' => $user->email,
            'token' => bcrypt($otp),
            'type' => $type,
            'expired_at' => $expiresAt,
            'created_at' => now(),
        ]);

        // Send OTP
        if ($type === 'email') {
            // Send OTP to email using Mailtrap
            \Log::info("OTP: $otp");
            Mail::to($identifier)->send(new ResetPasswordOtpMail($otp, $user->first_name));
        } else {
            // Send OTP via SMS using Twilio
            try {
                $this->twilioService->sendSms("{$countryCode}{$identifier}", "Your OTP is {$otp}. It is valid for 10 minutes.");
            } catch (\Exception $e) {
                \Log::error("Error sending OTP via Twilio: " . $e->getMessage());
                return response()->json(['error' => 'Failed to send OTP'], 500);
            }
        }

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    /**
     * @OA\Post(
     *     path="/reset-password",
     *     summary="Reset password using OTP",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"identifier", "type", "otp", "new_password"},
     *             @OA\Property(property="identifier", type="string", example="john.doe@example.com", description="Email or phone number."),
     *             @OA\Property(property="type", type="string", enum={"email", "phone"}, example="email", description="Specify the type of identifier."),
     *             @OA\Property(property="country_code", type="string", nullable=true, example="+1", description="Required if type is phone."),
     *             @OA\Property(property="otp", type="string", example="1234", description="4-digit OTP sent to email or phone."),
     *             @OA\Property(property="new_password", type="string", format="password", minLength=8, example="newPassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successful",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Password reset successful."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe")
     *             ),
     *             @OA\Property(property="token", type="string", example="sample-jwt-token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid OTP",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid OTP")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="OTP expired"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function resetPassword(Request $request)
    {
        // Validation for OTP and new password
        $validated = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'type' => 'required|string|in:email,phone',
            'country_code' => 'required_if:type,phone|string',
            'otp' => 'required|digits:4', // Ensure OTP is 4 digits
            'new_password' => 'required|string|min:8', // Ensure the password is strong
        ]);

        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 422);
        }

        $identifier = $validated->validated()['identifier'];
        $countryCode = $validated->validated()['country_code'] ?? null; // Country code will be null if not provided
        $otp = $validated->validated()['otp'];
        $newPassword = $validated->validated()['new_password'];
        $type = $validated->validated()['type'];

        // Find the user by email or phone
        $user = null;
        $passwordResetToken = null;
        $match = false;
        if ($type === 'email') {
            $user = User::where('email', $identifier)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            $byEmail = PasswordResetToken::where('email', $user->email)->first();
            if (!$byEmail) {
                return response()->json(['error' => 'No password reset token found.'], 404);
            }

            $passwordResetToken = PasswordResetToken::where('email', $user->email)->first();
            if ($passwordResetToken && Hash::check($otp, $passwordResetToken->token)) {
                $match = true;
            } else {
                return response()->json(['error' => 'Invalid OTP.'], 401);
            }
        } elseif ($type === 'phone') {
            $user = User::where('phone', $identifier)->where('country_code', $countryCode)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            $byEmail = PasswordResetToken::where('email', $user->email)->first();
            if (!$byEmail) {
                return response()->json(['error' => 'No password reset token found.'], 404);
            }

            $passwordResetToken = PasswordResetToken::where('email', $user->email)->first();
            if ($passwordResetToken && Hash::check($otp, $passwordResetToken->token)) {
                $match = true;
            } else {
                return response()->json(['error' => 'Invalid OTP.'], 401);
            }
        }

        // If user does not exist or OTP is invalid
        if (!$match) {
            return response()->json(['error' => 'Invalid OTP or user not found.'], 400);
        }
        \Log::info("OTP: $otp");

        // Check if OTP is expired
        if ($passwordResetToken->expired_at < now()) {
            PasswordResetToken::where('email', $passwordResetToken->email)->delete();
            return response()->json(['error' => 'OTP has expired.'], 400);
        }

        // Update the user's password
        $user->update(['password' => Hash::make($newPassword)]);

        // Delete the OTP record after successful password reset
        PasswordResetToken::where('email', $passwordResetToken->email)->delete();

        $token = $user->createToken('MarasemApp')->plainTextToken;

        return response()->json([
            'message' => 'Password reset successful.',
            'user' => $user,
            'token' => $token,
        ]);
    }

}
