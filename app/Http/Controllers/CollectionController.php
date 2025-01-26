<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Language;
use App\Models\Tag;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/collections",
     *     summary="List all collections with their latest three artworks",
     *     tags={"Collections"},
     *     @OA\Response(
     *         response=200,
     *         description="Collections fetched successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1, description="Collection ID"),
     *                 @OA\Property(property="name", type="string", example="Nature Collection", description="Collection name"),
     *                 @OA\Property(property="tags", type="array", @OA\Items(type="string", example="Landscape"), description="Tags associated with the collection"),
     *                 @OA\Property(property="followers", type="integer", example=200, description="Number of followers"),
     *                 @OA\Property(
     *                     property="latest_artworks",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1, description="Artwork ID"),
     *                         @OA\Property(property="name", type="string", example="Sunset Painting", description="Artwork name"),
     *                         @OA\Property(property="images", type="array", @OA\Items(type="string", example="https://example.com/image.jpg"), description="Artwork images"),
     *                         @OA\Property(property="art_type", type="string", example="Painting", description="Type of artwork")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function index()
    {
        $user = auth('sanctum')->user();
        $preferredLanguageId = $user ? $user->preferred_language : Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        $collections = Collection::with([
            'artworks.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            }
        ])->get();

        // Fetch tag translations based on the IDs present in collections
        $allTagIds = $collections->flatMap(function ($collection) {
            $tags = json_decode($collection->tags, true); // Decode the JSON string
            return is_array($tags) ? $tags : []; // Ensure it's an array
        })->unique();
        $tags = Tag::whereIn('id', $allTagIds)
            ->with([
                'translations' => function ($query) use ($preferredLanguageId) {
                    $query->where('language_id', $preferredLanguageId);
                }
            ])
            ->get()
            ->keyBy('id');

        return response()->json($collections->map(function ($collection) use ($tags, $preferredLanguageId) {
            // Collection translation
            $collectionTranslation = $collection->translations->first();
            $collection->name = $collectionTranslation->title ?? $collection->title;

            return [
                'id' => $collection->id,
                'name' => $collection->name,
                'tags' => collect(json_decode($collection->tags, true))->map(function ($tagId) use ($tags) {
                    $tag = $tags->get($tagId);
                    $tagTranslation = $tag ? $tag->translations->first() : null;
                    return [
                        'id' => $tagId,
                        'name' => $tagTranslation->name ?? $tag->name ?? null,
                    ];
                })->filter(), // Remove null values
                'followers' => $collection->followers,
                'latest_artworks' => $collection->artworks->map(function ($artwork) {
                    // Artwork translation
                    $artworkTranslation = $artwork->translations->first();
                    return [
                        'id' => $artwork->id,
                        'name' => $artworkTranslation->name ?? $artwork->name,
                        'images' => $artwork->images,
                        'art_type' => $artworkTranslation->art_type ?? $artwork->art_type,
                    ];
                }),
            ];
        }));
    }

    /**
     * @OA\Get(
     *     path="/collections/{id}",
     *     summary="View a specific collection and its artworks",
     *     tags={"Collections"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the collection",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Collection fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1, description="Collection ID"),
     *             @OA\Property(property="name", type="string", example="Nature Collection", description="Collection name"),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string", example="Landscape"), description="Tags associated with the collection"),
     *             @OA\Property(property="followers", type="integer", example=200, description="Number of followers"),
     *             @OA\Property(property="artwork_tags", type="array", @OA\Items(type="string", example="Sunset"), description="Unique tags from the artworks in this collection"),
     *             @OA\Property(
     *                 property="artworks",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1, description="Artwork ID"),
     *                     @OA\Property(property="name", type="string", example="Sunset Painting", description="Artwork name"),
     *                     @OA\Property(property="images", type="array", @OA\Items(type="string", example="https://example.com/image.jpg"), description="Artwork images"),
     *                     @OA\Property(property="art_type", type="string", example="Painting", description="Type of artwork"),
     *                     @OA\Property(property="artist", ref="#/components/schemas/User", description="Artist who created the artwork")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Collection not found"
     *     )
     * )
     */

    public function show($id)
    {
        $user = auth('sanctum')->user();
        $preferredLanguageId = $user ? $user->preferred_language : Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        $collection = Collection::with([
            'artworks.tags.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'artworks.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            }
        ])->findOrFail($id);

        // Collection translation
        $collectionTranslation = $collection->translations->first();
        $collection->name = $collectionTranslation->title ?? $collection->title;

        // Decode the tags JSON and fetch tag details
        $tagIds = collect(json_decode($collection->tags, true)); // Decode tags JSON
        $tags = Tag::whereIn('id', $tagIds)
            ->with([
                'translations' => function ($query) use ($preferredLanguageId) {
                    $query->where('language_id', $preferredLanguageId);
                }
            ])
            ->get();

        // Map tags with translations
        $translatedTags = $tagIds->map(function ($tagId) use ($tags) {
            $tag = $tags->firstWhere('id', $tagId); // Find the tag by ID
            if ($tag) {
                $tagTranslation = $tag->translations->first();
                return [
                    'id' => $tag->id,
                    'name' => $tagTranslation->name ?? $tag->name,
                ];
            }
            return null;
        })->filter(); // Remove null values

        // Extract all tags from the artworks with translations
        $artworkTags = $collection->artworks
            ->flatMap(function ($artwork) {
                return $artwork->tags->map(function ($tag) {
                    $tagTranslation = $tag->translations->first();
                    return [
                        'id' => $tag->id,
                        'name' => $tagTranslation->name ?? $tag->name,
                    ];
                });
            })
            ->unique('id')
            ->values();

        return response()->json([
            'id' => $collection->id,
            'name' => $collection->name,
            'tags' => $translatedTags, // Remove null values
            'followers' => $collection->followers,
            'artwork_tags' => $artworkTags, // Unique tags from artworks
            'artworks' => $collection->artworks->map(function ($artwork) {
                // Artwork translation
                $artworkTranslation = $artwork->translations->first();
                return [
                    'id' => $artwork->id,
                    'name' => $artworkTranslation->name ?? $artwork->name,
                    'images' => $artwork->images,
                    'art_type' => $artworkTranslation->art_type ?? $artwork->art_type,
                    'artist' => $artwork->artist,
                ];
            }),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/collections/{id}/follow",
     *     summary="Follow a collection",
     *     tags={"Collections"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the collection to follow",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Collection followed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Collection followed successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Already following the collection"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Collection not found"
     *     )
     * )
     */

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

    /**
     * @OA\Post(
     *     path="/collections/{id}/unfollow",
     *     summary="Unfollow a collection",
     *     tags={"Collections"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the collection to unfollow",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Collection unfollowed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Collection unfollowed successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Not following the collection"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Collection not found"
     *     )
     * )
     */

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
