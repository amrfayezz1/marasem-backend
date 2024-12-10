<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|string|email|max:255',
            'phone' => 'required_without:email|string',
            'country_code' => 'required_without:email|string',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->email && Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
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

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
