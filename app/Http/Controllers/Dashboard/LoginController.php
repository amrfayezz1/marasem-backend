<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /**
     * Show the login page.
     */
    public function showLoginForm()
    {
        // if (Auth::check()) {
        //     return redirect()->route('dashboard.index');
        // }
        return view('dashboard.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        // if already signed in, redirect to dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard.index');
        }

        $validator = Validator::make($request->all(), [
            'email' => 'nullable|string|email|max:255',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $credentials = ['password' => $request->password];

        if ($request->email) {
            $credentials['email'] = $request->email;
        }

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Check if user has admin privileges
            if (!in_array($user->is_admin, [1, 2])) {
                Auth::logout();
                return redirect()->route('login')->withErrors(['error' => 'Unauthorized access.']);
            }

            return redirect()->route('dashboard.index')->with('success', 'Login successful.');
        }

        return redirect()->back()->withErrors(['error' => 'Invalid credentials'])->withInput();
    }

    /**
     * Handle logout request.
     */
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
