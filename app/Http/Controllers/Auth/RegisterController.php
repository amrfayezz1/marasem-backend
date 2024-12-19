<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Address;
use App\Models\ArtistDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class RegisterController extends Controller
{
    /**
     * @OA\Post(
     *     path="/register",
     *     summary="Register a new user",
     *     tags={"Registration"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"first_name", "last_name", "email", "password", "phone", "country_code"},
     *             @OA\Property(property="first_name", type="string", maxLength=255, example="John"),
     *             @OA\Property(property="last_name", type="string", maxLength=255, example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="password123"),
     *             @OA\Property(property="phone", type="string", pattern="^[0-9]{7,15}$", example="1234567890"),
     *             @OA\Property(property="country_code", type="string", pattern="^\\+\\d{1,4}$", example="+1"),
     *             @OA\Property(property="is_artist", type="boolean", nullable=true, example=true, description="Optional. Indicates if the user is an artist.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User registered successfully."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="is_artist", type="boolean", example=true)
     *             ),
     *             @OA\Property(property="token", type="string", example="sample-jwt-token")
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
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'currency' => 'nullable|string|size:3',
            'phone' => [
                'required',
                'string',
                'unique:users',
                'regex:/^[0-9]{7,15}$/',
            ],
            'country_code' => [
                'required',
                'string',
                'regex:/^\+\d{1,4}$/',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'country_code' => $request->country_code,
            'preferred_currency' => $validated['currency'] ?? 'EGP',
        ]);

        // If the user is an artist, create their artist details record
        if ($request->has('is_artist') && $request->is_artist) {
            $user->is_artist = 1;
            $user->save();
            $artistDetail = ArtistDetail::create([
                'user_id' => $user->id,
                'registration_step' => 1,
                'completed' => false,
            ]);
        }

        // Automatically log in the user
        $token = $user->createToken('MarasemApp')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // Step 2: Handle social media links
    /**
     * @OA\Post(
     *     path="/add-social-media-links",
     *     summary="Add social media links for the user",
     *     tags={"Registration"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"social_media_link", "portfolio_link"},
     *             @OA\Property(property="social_media_link", type="string", format="url", example="https://twitter.com/user"),
     *             @OA\Property(property="portfolio_link", type="string", format="url", example="https://portfolio.com/user"),
     *             @OA\Property(property="website_link", type="string", format="url", nullable=true, example="https://website.com"),
     *             @OA\Property(property="other_link", type="string", format="url", nullable=true, example="https://other.com"),
     *             @OA\Property(property="summary", type="string", nullable=true, example="Artist specialized in digital art.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Social media links added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Social media links added successfully."),
     *             @OA\Property(property="artistDetail", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid step"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function addSocialMediaLinks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'social_media_link' => 'required|url',
            'portfolio_link' => 'required|url',
            'website_link' => 'nullable|url',
            'other_link' => 'nullable|url',
            'summary' => 'nullable|string',
        ]);

        $user = $request->user();
        $artistDetail = $user->artistDetails;
        if ($artistDetail->registration_step !== 1) {
            return response()->json(['error' => 'Invalid step.'], 400);
        }
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $artistDetail->update([
            'social_media_link' => $validated['social_media_link'],
            'portfolio_link' => $validated['portfolio_link'],
            'website_link' => $validated['website_link'],
            'other_link' => $validated['other_link'],
            'summary' => $validated['summary'],
            'registration_step' => 2,  // Step 2
        ]);

        return response()->json([
            'message' => 'Social media links added successfully.',
            'artistDetail' => $artistDetail,
        ]);
    }

    // Step 3.1: Get all categories
    /**
     * @OA\Get(
     *     path="/get-categories",
     *     summary="Fetch all categories",
     *     tags={"Registration"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of categories",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Painting")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getCategories()
    {
        $categories = \App\Models\Tag::all();
        return response()->json([
            'categories' => $categories,
        ]);
    }
    // Step 3.2: Choose categories
    /**
     * @OA\Post(
     *     path="/choose-categories",
     *     summary="Choose categories for the user",
     *     tags={"Registration"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="tags",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories chosen successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Categories chosen successfully."),
     *             @OA\Property(property="artistDetail", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid step"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function addCategories(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $user = $request->user();
        $artistDetail = $user->artistDetails;

        if ($artistDetail->registration_step !== 2) {
            return response()->json(['error' => 'Invalid step.'], 400);
        }
        // If validation fails, return the errors
        if ($validated->fails()) {
            return response()->json([
                'errors' => $validated->errors(),
            ], 422);
        }

        $tagIds = $validated->validated()['tags'];

        // Attach the categories to the user
        foreach ($tagIds as $tagId) {
            // Assuming the user has a 'categories' relationship defined
            $user->tags()->attach($tagId);
        }

        // Update the artist's registration step
        $artistDetail->update([
            'registration_step' => 3,
        ]);

        return response()->json([
            'message' => 'Categories chosen successfully.',
            'artistDetail' => $artistDetail,
        ]);
    }

    // Step 4: Add pickup location
    /**
     * @OA\Post(
     *     path="/add-pickup-location",
     *     summary="Add pickup location for the user",
     *     tags={"Registration"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"city", "zone", "address"},
     *             @OA\Property(property="city", type="string", example="Cairo"),
     *             @OA\Property(property="zone", type="string", example="Downtown"),
     *             @OA\Property(property="address", type="string", example="123 Main St, Cairo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pickup location added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Pickup location added successfully."),
     *             @OA\Property(property="artistDetail", type="object"),
     *             @OA\Property(
     *                 property="address",
     *                 type="object",
     *                 @OA\Property(property="city", type="string", example="Cairo"),
     *                 @OA\Property(property="zone", type="string", example="Downtown"),
     *                 @OA\Property(property="address", type="string", example="123 Main St, Cairo")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid step"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function addPickupLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city' => 'required|string',
            'zone' => 'required|string',
            'address' => 'required|string',
        ]);

        $user = $request->user();
        $artistDetail = $user->artistDetails;

        if ($artistDetail->registration_step !== 3) {
            return response()->json(['error' => 'Invalid step.'], 400);
        }
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update pickup location in ArtistDetail
        $address = Address::create([
            'user_id' => $user->id,
            'city' => $request->city,
            'zone' => $request->zone,
            'address' => $request->address,
            'is_default' => 1,
        ]);
        $artistDetail->completed = true; // Mark as completed
        $artistDetail->registration_step = 4; // Final step
        $artistDetail->save();

        return response()->json([
            'message' => 'Pickup location added successfully.',
            'artistDetail' => $artistDetail,
            'address' => $address,
        ]);
    }
}
