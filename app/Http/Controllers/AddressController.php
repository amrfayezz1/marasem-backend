<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function store(Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'city' => 'required|string|max:255',
            'zone' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'country_code' => 'nullable|string|max:5',
            'is_default' => 'nullable|boolean',
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Check if the address should be default
        if (isset($validated['is_default']) && $validated['is_default'] == true) {
            // If the user already has a default address, remove it
            $user->addresses()->where('is_default', true)->update(['is_default' => false]);
        }

        // Create the new address
        $address = $user->addresses()->create([
            'city' => $validated['city'],
            'zone' => $validated['zone'],
            'address' => $validated['address'],
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'country_code' => $validated['country_code'],
            'is_default' => $validated['is_default'] ?? false, // Default to false if not provided
        ]);

        // Return the newly created address
        return response()->json($address, 201);
    }
}
