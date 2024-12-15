<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * @OA\Post(
     *     path="/add-address",
     *     summary="Add a new address for the authenticated user",
     *     tags={"Address"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"city", "zone", "address"},
     *             @OA\Property(property="city", type="string", maxLength=255, example="Cairo"),
     *             @OA\Property(property="zone", type="string", maxLength=255, example="Downtown"),
     *             @OA\Property(property="address", type="string", maxLength=255, example="123 Main St"),
     *             @OA\Property(property="name", type="string", maxLength=255, nullable=true, example="John's Office"),
     *             @OA\Property(property="phone", type="string", maxLength=255, nullable=true, example="+1234567890"),
     *             @OA\Property(property="country_code", type="string", maxLength=5, nullable=true, example="+20"),
     *             @OA\Property(property="is_default", type="boolean", nullable=true, example=true, description="Set as default address")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Address created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="city", type="string", example="Cairo"),
     *             @OA\Property(property="zone", type="string", example="Downtown"),
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(property="name", type="string", nullable=true, example="John's Office"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+1234567890"),
     *             @OA\Property(property="country_code", type="string", nullable=true, example="+20"),
     *             @OA\Property(property="is_default", type="boolean", example=true),
     *             @OA\Property(property="user_id", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
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
