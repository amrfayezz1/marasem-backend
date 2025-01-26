<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Collection;
use App\Models\Tag;
use App\Models\Artwork;
use App\Models\Language;
use App\Models\ArtworkTranslation;
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
        // Determine the language to use for translations
        $user = auth('sanctum')->user();
        $preferredLanguageId = $user ? $user->preferred_language : null;
        $locale = $preferredLanguageId
            ? Language::find($preferredLanguageId)->code
            : $request->cookie('locale', 'en');
        $language = Language::where('code', $locale)->first();

        $languageId = $language ? $language->id : Language::where('code', 'en')->first()->id;

        $artworkId = $request->query('artwork_id');
        $offset = $request->query('offset', 0); // Default offset is 0
        $limit = $request->query('limit', 10); // Default limit is 10

        if ($artworkId) {
            // Fetch a single artwork by ID with translations
            $artwork = Artwork::with([
                'artist',
                'translations' => function ($query) use ($languageId) {
                    $query->where('language_id', $languageId);
                }
            ])
                ->withCount(['likes'])
                ->find($artworkId);

            if (!$artwork) {
                return response()->json(['error' => 'Artwork not found.'], 404);
            }

            // Add translated fields
            $translation = $artwork->translations->first();
            $artwork->name = $translation->name ?? $artwork->name;
            $artwork->art_type = $translation->art_type ?? $artwork->art_type;
            $artwork->description = $translation->description ?? $artwork->description;

            // Add liked status
            $artwork->liked = $user
                ? ArtworkLike::where('user_id', $user->id)
                    ->where('artwork_id', $artwork->id)
                    ->exists()
                : false;

            return response()->json([
                'artwork' => $artwork,
            ]);
        }

        // Fetch multiple artworks with translations
        $artworks = Artwork::with([
            'artist',
            'translations' => function ($query) use ($languageId) {
                $query->where('language_id', $languageId);
            }
        ])
            ->withCount(['likes'])
            ->offset($offset)
            ->limit($limit)
            ->orderBy('created_at', 'desc')
            ->get();

        // Add translated fields and liked status
        foreach ($artworks as $artwork) {
            $translation = $artwork->translations->first();
            $artwork->name = $translation->name ?? $artwork->name;
            $artwork->art_type = $translation->art_type ?? $artwork->art_type;
            $artwork->description = $translation->description ?? $artwork->description;

            $artistTranslation = $artwork->artist->translations->first();
            $artwork->artist->first_name = $artistTranslation->first_name ?? $artwork->artist->first_name;
            $artwork->artist->last_name = $artistTranslation->last_name ?? $artwork->artist->last_name;

            $artwork->liked = $user
                ? ArtworkLike::where('user_id', $user->id)
                    ->where('artwork_id', $artwork->id)
                    ->exists()
                : false;
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
        $user = auth('sanctum')->user();
        $preferredLanguage = $user ? $user->preferred_language : 'en'; // Use user's preferred language or fallback to 'en'

        $language = Language::where('code', $preferredLanguage)->first();

        // Fetch collections with translations
        $collections = Collection::with([
            'translations' => function ($query) use ($language) {
                if ($language) {
                    $query->where('language_id', $language->id);
                }
            }
        ])
            ->withCount(['artworks']) // Assuming a relation artworks exists in the Collection model
            ->orderByDesc('artworks_count')
            ->get()
            ->map(function ($collection) {
                // Get the translated title or fallback to the original
                $translation = $collection->translations->first();
                $collection->title = $translation->title ?? $collection->title;
                return $collection;
            });

        // Fetch tags with translations and usage count
        $tags = Tag::select('tags.id', 'tags.category_id', 'tags.name', 'tags.created_at', 'tags.updated_at')
            ->leftJoin('artwork_tag', 'tags.id', '=', 'artwork_tag.tag_id')
            ->selectRaw('COUNT(artwork_tag.artwork_id) as usage_count')
            ->with([
                'translations' => function ($query) use ($language) {
                    if ($language) {
                        $query->where('language_id', $language->id);
                    }
                }
            ])
            ->groupBy('tags.id', 'tags.category_id', 'tags.name', 'tags.created_at', 'tags.updated_at')
            ->orderByDesc('usage_count')
            ->get()
            ->map(function ($tag) {
                // Get the translated name or fallback to the original
                $translation = $tag->translations->first();
                $tag->name = $translation->name ?? $tag->name;
                return $tag;
            });

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
        $user = auth('sanctum')->user();
        $locale = $user->preferred_language ?? 1;
        $language = Language::where('id', $locale)->first();
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
            'artist_id' => $user->id,
            'name' => $validated['name'], // Save default name
            'images' => json_encode($uploadedImages),
            'art_type' => $validated['art_type'], // Save default art type
            'artwork_status' => $validated['artwork_status'],
            'sizes_prices' => json_encode(array_combine($validated['sizes'], $validated['prices'])),
            'description' => $validated['description'], // Save default description
            'customizable' => $validated['customizable'],
            'duration' => $validated['customizable'] ? $validated['duration'] : null,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
        ]);

        // Save translation for the current language
        if ($language) {
            ArtworkTranslation::create([
                'artwork_id' => $artwork->id,
                'language_id' => $language->id,
                'name' => $validated['name'],
                'art_type' => $validated['art_type'],
                'description' => $validated['description'],
            ]);
        }

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
     * @OA\Put(
     *     path="/artworks/{id}",
     *     summary="Update an existing artwork",
     *     tags={"Artworks"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the artwork to update",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", nullable=true, example="Sunset Painting"),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string", format="binary")),
     *             @OA\Property(property="art_type", type="string", nullable=true, example="Painting"),
     *             @OA\Property(property="artwork_status", type="string", nullable=true, example="Available"),
     *             @OA\Property(property="sizes", type="array", @OA\Items(type="string", example="24x36")),
     *             @OA\Property(property="prices", type="array", @OA\Items(type="number", format="float", example=200.50)),
     *             @OA\Property(property="description", type="string", nullable=true, example="A beautiful sunset painting."),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="integer", example=1)),
     *             @OA\Property(property="collections", type="array", @OA\Items(type="integer", example=2)),
     *             @OA\Property(property="customizable", type="boolean", nullable=true, example=true),
     *             @OA\Property(property="duration", type="string", nullable=true, example="7 days", description="Required if customizable is true")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Artwork updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Artwork updated successfully."),
     *             @OA\Property(property="artwork", ref="#/components/schemas/Artwork")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to update this artwork"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Artwork not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */

    public function updateArtwork(Request $request, $id)
    {
        $artwork = Artwork::find($id);

        if (!$artwork) {
            return response()->json(['error' => 'Artwork not found.'], 404);
        }

        // Ensure the logged-in user is the owner of the artwork
        if ($artwork->artist_id !== Auth::id()) {
            return response()->json(['error' => 'You are not authorized to update this artwork.'], 403);
        }

        // Get the user's preferred language
        $user = auth('sanctum')->user();
        $locale = $user->preferred_language ?? '1';
        $language = Language::where('id', $locale)->first();

        // Validate request
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'images' => 'nullable|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg',
            'art_type' => 'nullable|string',
            'artwork_status' => 'nullable|string',
            'sizes' => 'nullable|array',
            'prices' => 'nullable|array',
            'sizes.*' => 'required_with:prices|string',
            'prices.*' => 'required_with:sizes|numeric',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'collections' => 'nullable|array',
            'collections.*' => 'exists:collections,id',
            'customizable' => 'nullable|boolean',
            'duration' => 'nullable|string',
        ]);

        if (isset($validated['sizes']) && isset($validated['prices'])) {
            if (count($validated['sizes']) !== count($validated['prices'])) {
                return response()->json(['error' => 'Sizes and prices must have matching counts.'], 400);
            }
        }

        // Update images
        $uploadedImages = $artwork->images ? json_decode($artwork->images, true) : [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('artworks', 'public');
                $uploadedImages[] = asset('storage/' . $path);
            }
        }

        // Calculate min and max prices
        $prices = isset($validated['prices']) ? $validated['prices'] : json_decode($artwork->sizes_prices, true);
        $minPrice = min($prices);
        $maxPrice = max($prices);

        // Update artwork in the main table
        $artwork->update([
            'name' => $validated['name'] ?? $artwork->name,
            'images' => json_encode($uploadedImages),
            'art_type' => $validated['art_type'] ?? $artwork->art_type,
            'artwork_status' => $validated['artwork_status'] ?? $artwork->artwork_status,
            'sizes_prices' => isset($validated['sizes']) && isset($validated['prices'])
                ? json_encode(array_combine($validated['sizes'], $validated['prices']))
                : $artwork->sizes_prices,
            'description' => $validated['description'] ?? $artwork->description,
            'customizable' => $validated['customizable'] ?? $artwork->customizable,
            'duration' => $validated['customizable'] ?? $artwork->customizable ? ($validated['duration'] ?? $artwork->duration) : null,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
        ]);

        // Update or create translations in the artwork_translations table
        if ($language) {
            ArtworkTranslation::updateOrCreate(
                ['artwork_id' => $artwork->id, 'language_id' => $language->id],
                [
                    'name' => $validated['name'] ?? $artwork->name,
                    'art_type' => $validated['art_type'] ?? $artwork->art_type,
                    'description' => $validated['description'] ?? $artwork->description,
                ]
            );
        }

        // Sync tags and collections
        if (isset($validated['tags'])) {
            $artwork->tags()->sync($validated['tags']);
        }

        if (isset($validated['collections'])) {
            $artwork->collections()->sync($validated['collections']);
        }

        return response()->json([
            'message' => 'Artwork updated successfully.',
            'artwork' => $artwork,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/artworks/{id}",
     *     summary="Delete an artwork",
     *     tags={"Artworks"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the artwork to delete",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Artwork deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Artwork deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to delete this artwork"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Artwork not found"
     *     )
     * )
     */

    public function deleteArtwork($id)
    {
        $artwork = Artwork::find($id);

        if (!$artwork) {
            return response()->json(['error' => 'Artwork not found.'], 404);
        }

        // Ensure the logged-in user is the owner of the artwork
        if ($artwork->artist_id !== Auth::id()) {
            return response()->json(['error' => 'You are not authorized to delete this artwork.'], 403);
        }

        // Delete associated tags, collections, and other relationships
        $artwork->tags()->detach();
        $artwork->collections()->detach();

        // Optionally delete associated images from storage
        $images = json_decode($artwork->images, true);
        if ($images) {
            foreach ($images as $imagePath) {
                $relativePath = str_replace(asset('storage/'), '', $imagePath);
                \Storage::disk('public')->delete($relativePath);
            }
        }

        // Delete the artwork
        $artwork->delete();

        return response()->json(['message' => 'Artwork deleted successfully.']);
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
        $artist = $artwork->artist;
        $artist->increment('appreciations_count');

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
        $artist = $artwork->artist;
        if ($artist->appreciations_count > 0) {
            $artist->decrement('appreciations_count');
        }

        return response()->json(['message' => 'Unliked successfully', 'likes_count' => $artwork->likes_count], 200);
    }

    /**
     * @OA\Get(
     *     path="/artworks/{id}",
     *     summary="View a specific artwork",
     *     tags={"Artworks"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the artwork to view",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Artwork details",
     *         @OA\JsonContent(ref="#/components/schemas/Artwork")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Artwork not found"
     *     )
     * )
     */

    public function viewArtwork(Request $request, $id)
    {
        $user = auth('sanctum')->user();
        $preferredLanguageId = $user ? $user->preferred_language : null;
        $locale = $preferredLanguageId
            ? Language::find($preferredLanguageId)->code
            : $request->cookie('locale', 'en');
        $language = Language::where('code', $locale)->first();
        $languageId = $language ? $language->id : Language::where('code', 'en')->first()->id;

        // Fetch the artwork with translations and artist
        $artwork = Artwork::with([
            'translations' => function ($query) use ($languageId) {
                $query->where('language_id', $languageId);
            },
            'artist.translations' => function ($query) use ($languageId) {
                $query->where('language_id', $languageId);
            }
        ])->find($id);

        if (!$artwork) {
            return response()->json(['message' => 'Artwork not found'], 404);
        }

        // Increment views for unique visitors
        $ip = $request->ip();
        $uniqueKey = "artwork_view_{$id}_{$ip}";

        if (!cache()->has($uniqueKey)) {
            cache()->put($uniqueKey, true, 86400); // Cache for 1 day
            $artwork->increment('views_count');
        }

        // Add translated fields
        $artworkTranslation = $artwork->translations->first();
        $artwork->name = $artworkTranslation->name ?? $artwork->name;
        $artwork->art_type = $artworkTranslation->art_type ?? $artwork->art_type;
        $artwork->description = $artworkTranslation->description ?? $artwork->description;

        // Add translated artist fields
        $artistTranslation = $artwork->artist->translations->first();
        $artwork->artist->first_name = $artistTranslation->first_name ?? $artwork->artist->first_name;
        $artwork->artist->last_name = $artistTranslation->last_name ?? $artwork->artist->last_name;

        return response()->json($artwork);
    }
}
