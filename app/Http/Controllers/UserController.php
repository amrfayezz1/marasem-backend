<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/change-currency",
     *     summary="Change the user's preferred currency",
     *     tags={"User"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"currency"},
     *             @OA\Property(
     *                 property="currency",
     *                 type="string",
     *                 example="USD",
     *                 description="The 3-letter currency code to set as preferred currency."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Currency updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Currency updated successfully."),
     *             @OA\Property(property="preferred_currency", type="string", example="USD", description="The updated preferred currency.")
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
