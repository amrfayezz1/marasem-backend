<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function changeCurrency(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'currency' => 'required|string|size:3',
        ]);

        // Update the preferred currency
        $user->update(['preferred_currency' => $validated['currency']]);

        return response()->json([
            'message' => 'Currency updated successfully.',
            'preferred_currency' => $user->preferred_currency,
        ]);
    }
}
