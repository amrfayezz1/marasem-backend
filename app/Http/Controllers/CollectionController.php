<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    /**
     * Show all collections with their latest three artworks.
     */
    public function index()
    {
        $collections = Collection::with([
            'artworks' => function ($query) {
                $query->latest()->take(3); // Fetch latest 3 artworks for each collection
            }
        ])->get();

        return response()->json($collections->map(function ($collection) {
            return [
                'id' => $collection->id,
                'name' => $collection->name,
                'tags' => $collection->tags,
                'followers' => $collection->followers,
                'latest_artworks' => $collection->artworks->map(function ($artwork) {
                    return [
                        'id' => $artwork->id,
                        'name' => $artwork->name,
                        'images' => $artwork->images,
                        'art_type' => $artwork->art_type,
                    ];
                }),
            ];
        }));
    }

    /**
     * Show a specific collection along with all its artworks.
     */
    public function show($id)
    {
        $collection = Collection::with('artworks.tags')->findOrFail($id);

        // Extract all tags from the artworks
        $tags = $collection->artworks
            ->flatMap(function ($artwork) {
                return $artwork->tags->pluck('name');
            })
            ->unique()
            ->values();

        return response()->json([
            'id' => $collection->id,
            'name' => $collection->name,
            'tags' => $collection->tags,
            'followers' => $collection->followers,
            'artwork_tags' => $tags, // Unique tags from artworks
            'artworks' => $collection->artworks->map(function ($artwork) {
                return [
                    'id' => $artwork->id,
                    'name' => $artwork->name,
                    'images' => $artwork->images,
                    'art_type' => $artwork->art_type,
                    'artist' => $artwork->artist,
                ];
            }),
        ]);
    }

    public function follow($id)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'You must be logged in to follow a collection.'], 401);
        }

        $collection = Collection::findOrFail($id);

        // Check if already followed
        if ($collection->followers()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You already follow this collection.'], 400);
        }

        // Attach user to collection
        $collection->followers()->attach($user->id);

        // Increment followers count
        $collection->increment('followers');

        return response()->json(['message' => 'Collection followed successfully.']);
    }

    public function unfollow($id)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'You must be logged in to unfollow a collection.'], 401);
        }

        $collection = Collection::findOrFail($id);

        // Check if not followed
        if (!$collection->followers()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You do not follow this collection.'], 400);
        }

        // Detach user from collection
        $collection->followers()->detach($user->id);

        // Decrement followers count
        $collection->decrement('followers');

        return response()->json(['message' => 'Collection unfollowed successfully.']);
    }

}
