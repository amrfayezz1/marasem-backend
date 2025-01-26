<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Address;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FilterController extends Controller
{
    /**
     * @OA\Get(
     *     path="/filters",
     *     summary="Get available filters for artworks",
     *     tags={"Filters"},
     *     @OA\Response(
     *         response=200,
     *         description="Filters fetched successfully",
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
     *             ),
     *             @OA\Property(
     *                 property="tags",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="name", type="string", example="Landscape"),
     *                     @OA\Property(property="category_id", type="integer", example=1),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Painting")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="locations",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="city", type="string", example="Cairo")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function getFilters()
    {
        $user = auth('sanctum')->user();
        $preferredLanguageId = $user ? $user->preferred_language : null;
        $locale = $preferredLanguageId
            ? Language::find($preferredLanguageId)->code
            : request()->cookie('locale', 'en');
        $language = Language::where('code', $locale)->first();
        $languageId = $language ? $language->id : Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        // Fetch categories with translations
        $categories = Category::with([
            'translations' => function ($query) use ($languageId) {
                $query->where('language_id', $languageId);
            }
        ])->get()->map(function ($category) {
            $translation = $category->translations->first();
            $category->name = $translation->name ?? $category->name;
            return $category;
        });

        // Fetch tags with translations and their categories
        $tags = Tag::with([
            'translations' => function ($query) use ($languageId) {
                $query->where('language_id', $languageId);
            },
            'category.translations' => function ($query) use ($languageId) {
                $query->where('language_id', $languageId);
            }
        ])->get()->map(function ($tag) {
            $tagTranslation = $tag->translations->first();
            $tag->name = $tagTranslation->name ?? $tag->name;

            $categoryTranslation = $tag->category->translations->first();
            $tag->category->name = $categoryTranslation->name ?? $tag->category->name;

            return $tag;
        });

        // Fetch unique locations
        $locationsFromAddresses = Address::select('city')
            ->distinct()
            ->whereHas('user.artworks')
            ->pluck('city');

        $locationsFromPickup = DB::table('artists_pickup_locations')
            ->distinct()
            ->join('users', 'artists_pickup_locations.artist_id', '=', 'users.id')
            ->join('artworks', 'users.id', '=', 'artworks.artist_id')
            ->whereNotNull('artists_pickup_locations.city')
            ->pluck('artists_pickup_locations.city');

        // Merge and get unique cities
        $locations = $locationsFromAddresses->merge($locationsFromPickup)->unique()->values();

        return response()->json([
            'categories' => $categories,
            'tags' => $tags,
            'locations' => $locations
        ]);
    }

    /**
     * @OA\Post(
     *     path="/filters/apply",
     *     summary="Apply filters to fetch artworks",
     *     tags={"Filters"},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="category", type="array", @OA\Items(type="integer", example=1)),
     *             @OA\Property(property="location", type="array", @OA\Items(type="string", example="Cairo")),
     *             @OA\Property(property="price_from", type="number", format="float", example=50.0),
     *             @OA\Property(property="price_to", type="number", format="float", example=500.0),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="integer", example=10)),
     *             @OA\Property(property="sort_by", type="string", enum={"best_selling", "most_liked", "price_low_to_high", "price_high_to_low"}, example="most_liked")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Filtered artworks",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Artwork")
     *         )
     *     )
     * )
     */

    public function applyFilters(Request $request)
    {
        $categoryIds = $request->input('category', []);
        $locations = $request->input('location', []);
        $priceFrom = $request->input('price_from');
        $priceTo = $request->input('price_to');
        $tagIds = $request->input('tags', []);
        $sortBy = $request->input('sort_by');

        $user = auth('sanctum')->user();
        $preferredLanguageId = $user ? $user->preferred_language : null;
        $locale = $preferredLanguageId
            ? Language::find($preferredLanguageId)->code
            : $request->cookie('locale', 'en');
        $language = Language::where('code', $locale)->first();
        $languageId = $language ? $language->id : Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        $query = Artwork::query()
            ->with([
                'translations' => function ($query) use ($languageId) {
                    $query->where('language_id', $languageId);
                },
                'artist.translations' => function ($query) use ($languageId) {
                    $query->where('language_id', $languageId);
                }
            ]);

        // Filter by category
        if (!empty($categoryIds)) {
            $query->whereHas('tags.category', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        // Filter by tags
        if (!empty($tagIds)) {
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        // Filter by location (from both addresses and artists_pickup_locations)
        if (!empty($locations)) {
            $query->where(function ($query) use ($locations) {
                $query->whereHas('artist.addresses', function ($q) use ($locations) {
                    $q->whereIn('city', $locations);
                })
                    ->orWhereHas('artist.pickupLocations', function ($q) use ($locations) {
                        $q->whereIn('city', $locations);
                    });
            });
        }

        // Filter by price range
        if ($priceFrom !== null) {
            $query->where('max_price', '>=', $priceFrom);
        }
        if ($priceTo !== null) {
            $query->where('min_price', '<=', $priceTo);
        }

        // Sorting logic
        if ($sortBy) {
            switch ($sortBy) {
                case 'best_selling':
                    $query->withCount('orderItems')
                        ->orderBy('order_items_count', 'desc');
                    break;

                case 'most_liked':
                    $query->orderBy('likes_count', 'desc');
                    break;

                case 'price_low_to_high':
                    $query->orderBy('min_price', 'asc');
                    break;

                case 'price_high_to_low':
                    $query->orderBy('max_price', 'desc');
                    break;
            }
        }

        $artworks = $query->get();

        // Add translations to the response
        foreach ($artworks as $artwork) {
            // Artwork translations
            $artworkTranslation = $artwork->translations->first();
            $artwork->name = $artworkTranslation->name ?? $artwork->name;
            $artwork->art_type = $artworkTranslation->art_type ?? $artwork->art_type;
            $artwork->description = $artworkTranslation->description ?? $artwork->description;

            // Artist translations
            $artistTranslation = $artwork->artist->translations->first();
            $artwork->artist->first_name = $artistTranslation->first_name ?? $artwork->artist->first_name;
            $artwork->artist->last_name = $artistTranslation->last_name ?? $artwork->artist->last_name;
        }

        return response()->json($artworks);
    }

    /**
     * @OA\Get(
     *     path="/search",
     *     summary="Search for artworks by name or description",
     *     tags={"Filters"},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=false,
     *         description="Search query for artwork name or description",
     *         @OA\Schema(type="string", example="sunset")
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         description="Pagination offset",
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Pagination limit",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results with pagination",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="artworks", type="array", @OA\Items(ref="#/components/schemas/Artwork")),
     *             @OA\Property(property="has_more", type="boolean", example=true)
     *         )
     *     )
     * )
     */

    public function search(Request $request)
    {
        $q = $request->query('q', '');
        $offset = (int) $request->query('offset', 0);
        $limit = (int) $request->query('limit', 10);

        // Determine the language to use for translations
        $user = auth('sanctum')->user();
        $preferredLanguageId = $user ? $user->preferred_language : null;
        $locale = $preferredLanguageId
            ? Language::find($preferredLanguageId)->code
            : $request->cookie('locale', 'en');
        $language = Language::where('code', $locale)->first();
        $languageId = $language ? $language->id : Language::where('code', request()->cookie('locale', 'en'))->first()->id;
        \Log::info("Language ID: $languageId");

        // Search in translations
        $artworksQuery = Artwork::query()
            ->with([
                'artist.translations' => function ($query) use ($languageId) {
                    $query->where('language_id', $languageId);
                },
                'translations' => function ($query) use ($languageId) {
                    $query->where('language_id', $languageId);
                }
            ])
            ->whereHas('translations', function ($query) use ($q, $languageId) {
                $query->where('language_id', $languageId)
                    ->where(function ($subQuery) use ($q) {
                        $subQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('description', 'like', "%{$q}%");
                    });
            });

        // Get the total count for pagination
        $totalCount = $artworksQuery->count();

        // Fetch the artworks with pagination
        $artworks = $artworksQuery
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Add translated fields and artist translations
        foreach ($artworks as $artwork) {
            // Artwork translations
            $artworkTranslation = $artwork->translations->first();
            $artwork->name = $artworkTranslation->name ?? $artwork->name;
            $artwork->art_type = $artworkTranslation->art_type ?? $artwork->art_type;
            $artwork->description = $artworkTranslation->description ?? $artwork->description;

            // Artist translations
            $artistTranslation = $artwork->artist->translations->first();
            $artwork->artist->first_name = $artistTranslation->first_name ?? $artwork->artist->first_name;
            $artwork->artist->last_name = $artistTranslation->last_name ?? $artwork->artist->last_name;
        }

        // Determine if more records are available after these
        $hasMore = $totalCount > ($offset + $limit);

        return response()->json([
            'artworks' => $artworks,
            'has_more' => $hasMore,
        ]);
    }
}