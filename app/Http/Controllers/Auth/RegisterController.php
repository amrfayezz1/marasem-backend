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
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
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

    // Step 3: Choose categories
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
            'is_main' => 1,
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
