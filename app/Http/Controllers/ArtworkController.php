<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Collection;
use App\Models\Tag;
use App\Models\Artwork;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\ArtworkLike;


class ArtworkController extends Controller
{
    /**
     * @OA\Get(
     *     path="/artworks",
     *     summary="Fetch artworks with pagination and liked status",
     *     tags={"Artworks"},
     *     @OA\Parameter(
     *         name="artwork_id",
     *         in="query",
     *         required=false,
     *         description="ID of a specific artwork to fetch",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         description="Pagination offset (default is 0)",
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Pagination limit (default is 10)",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of artworks",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="artworks", type="array", @OA\Items(ref="#/components/schemas/Artwork")),
     *             @OA\Property(property="has_more", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Artwork not found"
     *     )
     * )
     */
    public function fetchArtworks(Request $request)
    {
        \Log::info($request->all());
        $artworkId = $request->query('artwork_id');
        $offset = $request->query('offset', 0); // Default offset is 0
        $limit = $request->query('limit', default: 10); // Default limit is 10
        $user = auth('sanctum')->user();

        if ($artworkId) {
            // Fetch a single artwork by ID
            $artwork = Artwork::with('artist')
                ->withCount(['likes'])
                ->find($artworkId);

            if (!$artwork) {
                return response()->json(['error' => 'Artwork not found.'], 404);
            }

            // Add liked status for the single artwork
            $artwork->liked = $user
                ? ArtworkLike::where('user_id', $user->id)
                    ->where('artwork_id', $artwork->id)
                    ->exists()
                : false;

            return response()->json([
                'artwork' => $artwork,
            ]);
        }

        // Fetch artworks with artist and liked status
        $artworks = Artwork::with('artist')
            ->withCount(['likes'])
            ->offset($offset)
            ->limit($limit)
            ->orderBy('created_at', 'desc')
            ->get();

        // Add liked status for each artwork
        if ($user) {
            foreach ($artworks as $artwork) {
                $artwork->liked = $user
                    ? ArtworkLike::where('user_id', $user->id)
                        ->where('artwork_id', $artwork->id)
                        ->exists()
                    : false;
            }
        }
        // Check if there are more artworks
        $hasMore = Artwork::count() > ($offset + $limit);

        return response()->json([
            'artworks' => $artworks,
            'has_more' => $hasMore,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/collections-tags",
     *     summary="Get collections and tags with their usage count",
     *     tags={"Artworks"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Collections and tags fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="collections", type="array", @OA\Items(ref="#/components/schemas/Collection")),
     *             @OA\Property(property="tags", type="array", @OA\Items(ref="#/components/schemas/Tag"))
     *         )
     *     )
     * )
     */
    public function getCollectionsAndTags()
    {
        $collections = Collection::withCount([
            'artworks' // Assuming a relation artworks exists in the Collection model
        ])->orderByDesc('artworks_count')->get();

        $tags = Tag::select('tags.id', 'tags.category_id', 'tags.name', 'tags.created_at', 'tags.updated_at')
            ->leftJoin('artwork_tag', 'tags.id', '=', 'artwork_tag.tag_id')
            ->selectRaw('COUNT(artwork_tag.artwork_id) as usage_count')
            ->groupBy('tags.id', 'tags.name', 'tags.created_at', 'tags.updated_at')
            ->orderByDesc('usage_count')
            ->get();

        return response()->json([
            'collections' => $collections,
            'tags' => $tags,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/artworks",
     *     summary="Create a new artwork",
     *     tags={"Artworks"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name", "art_type", "artwork_status", "sizes", "prices", "description", "customizable"},
     *             @OA\Property(property="name", type="string", example="Sunset Painting"),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string", format="binary")),
     *             @OA\Property(property="art_type", type="string", example="Painting"),
     *             @OA\Property(property="artwork_status", type="string", example="Available"),
     *             @OA\Property(property="sizes", type="array", @OA\Items(type="string", example="24x36")),
     *             @OA\Property(property="prices", type="array", @OA\Items(type="number", format="float", example=200.50)),
     *             @OA\Property(property="description", type="string", example="A beautiful sunset painting."),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="integer", example=1)),
     *             @OA\Property(property="collections", type="array", @OA\Items(type="integer", example=2)),
     *             @OA\Property(property="customizable", type="boolean", example=true),
     *             @OA\Property(property="duration", type="string", nullable=true, example="7 days", description="Required if customizable is true")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Artwork created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Artwork created successfully."),
     *             @OA\Property(property="artwork", ref="#/components/schemas/Artwork")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Sizes and prices mismatch or other validation error"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function createArtwork(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'images' => 'nullable|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg',
            'art_type' => 'required|string',
            'artwork_status' => 'required|string',
            'sizes' => 'required|array',
            'prices' => 'required|array',
            'sizes.*' => 'required|string',
            'prices.*' => 'required|numeric',
            'description' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'collections' => 'nullable|array',
            'collections.*' => 'exists:collections,id',
            'customizable' => 'required|boolean',
            'duration' => 'nullable|string',
        ]);
        $request->validate([
            'duration' => Rule::requiredIf($request->customizable == true),
        ]);

        if (count($validated['sizes']) !== count($validated['prices'])) {
            return response()->json(['error' => 'Sizes and prices must have matching counts.'], 400);
        }
    
        // Calculate min and max prices
        $prices = $validated['prices'];
        $minPrice = min($prices);
        $maxPrice = max($prices);

        // Upload images
        $uploadedImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('artworks', 'public');
                $uploadedImages[] = asset('storage/' . $path);
            }
        }

        // Create artwork
        $artwork = Artwork::create([
            'artist_id' => Auth::user()->id,
            'name' => $validated['name'],
            'images' => json_encode($uploadedImages),
            'art_type' => $validated['art_type'],
            'artwork_status' => $validated['artwork_status'],
            'sizes_prices' => json_encode(array_combine($validated['sizes'], $validated['prices'])),
            'description' => $validated['description'],
            'customizable' => $validated['customizable'],
            'duration' => $validated['customizable'] ? $validated['duration'] : null,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
        ]);

        // Attach tags and collections
        if (!empty($validated['tags'])) {
            $artwork->tags()->attach($validated['tags']);
        }

        if (!empty($validated['collections'])) {
            $artwork->collections()->attach($validated['collections']);
        }

        return response()->json([
            'message' => 'Artwork created successfully.',
            'artwork' => $artwork,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/artworks/{id}/like",
     *     summary="Like an artwork",
     *     tags={"Artworks"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the artwork to like",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Artwork liked successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Liked successfully"),
     *             @OA\Property(property="likes_count", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Artwork not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Already liked"
     *     )
     * )
     */
    public function like(Request $request, $id)
    {
        $user = Auth::user();
        $artwork = Artwork::find($id);
        if (!$artwork) {
            return response()->json(['message' => 'Artwork not found'], 404);
        }
        // Check if the user already liked the artwork
        if (ArtworkLike::where('user_id', $user->id)->where('artwork_id', $id)->exists()) {
            return response()->json(['message' => 'Already liked'], 400);
        }
        // Create a new like
        ArtworkLike::create([
            'user_id' => $user->id,
            'artwork_id' => $artwork->id,
        ]);
        // Increment the likes count
        $artwork->increment('likes_count');

        return response()->json(['message' => 'Liked successfully', 'likes_count' => $artwork->likes_count], 201);
    }

    /**
     * @OA\Delete(
     *     path="/artworks/{id}/like",
     *     summary="Unlike an artwork",
     *     tags={"Artworks"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the artwork to unlike",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Artwork unliked successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unliked successfully"),
     *             @OA\Property(property="likes_count", type="integer", example=4)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Artwork or like not found"
     *     )
     * )
     */
    public function unlike(Request $request, $id)
    {
        $user = Auth::user();
        $artwork = Artwork::find($id);
        if (!$artwork) {
            return response()->json(['message' => 'Artwork not found'], 404);
        }

        // Find the like record
        $like = ArtworkLike::where('user_id', $user->id)->where('artwork_id', $id)->first();
        if (!$like) {
            return response()->json(['message' => 'Like not found'], 404);
        }

        // Delete the like
        $like->delete();
        // Decrement the likes count
        if ($artwork->likes_count > 0) {
            $artwork->decrement('likes_count');
        }

        return response()->json(['message' => 'Unliked successfully', 'likes_count' => $artwork->likes_count], 200);
    }
}
