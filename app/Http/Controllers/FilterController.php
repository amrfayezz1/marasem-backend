<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FilterController extends Controller
{
    public function getFilters()
    {
        // Fetch categories
        $categories = Category::select('id', 'name')->get();

        // Fetch tags
        $tags = Tag::select('id', 'name', 'category_id')
            ->with('category:id,name')
            ->get();

        // Fetch locations
        $locations = Address::select('city')
            ->distinct()
            ->whereHas('user.artworks')
            ->get();

        return response()->json([
            'categories' => $categories,
            'tags' => $tags,
            'locations' => $locations
        ]);
    }

    public function applyFilters(Request $request)
    {
        $categoryIds = $request->input('category', []);
        $locations = $request->input('location', []);
        $priceFrom = $request->input('price_from');
        $priceTo = $request->input('price_to');
        $tagIds = $request->input('tags', []);

        $query = Artwork::query();

        // Filter by category (AND dimension)
        // OR logic within the category array:
        // An artwork qualifies if it has at least one tag whose category_id is in $categoryIds
        if (!empty($categoryIds)) {
            $query->whereHas('tags.category', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        // Filter by tags (AND dimension)
        // Artwork must have at least one of the given tags if tagIds is not empty.
        if (!empty($tagIds)) {
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        // Filter by location (AND dimension)
        // Artwork must come from an artist who has an address in one of these cities.
        if (!empty($locations)) {
            $query->whereHas('artist.addresses', function ($q) use ($locations) {
                $q->whereIn('city', $locations);
            });
        }

        $artworks = $query->get();

        // Filter by price range after fetching,
        // since sizes_prices is JSON (or consider storing min/max price to filter in DB)
        if ($priceFrom !== null || $priceTo !== null) {
            $artworks = $artworks->filter(function ($artwork) use ($priceFrom, $priceTo) {
                $sizesPrices = json_decode($artwork->sizes_prices, true);

                // Extract all prices from the JSON
                $allPrices = array_values($sizesPrices);
                $minPrice = min($allPrices);
                $maxPrice = max($allPrices);

                // Check price range logic
                // The artwork qualifies if it overlaps with the given price range
                $meetsMin = ($priceFrom === null) || ($maxPrice >= $priceFrom);
                $meetsMax = ($priceTo === null) || ($minPrice <= $priceTo);

                return $meetsMin && $meetsMax;
            })->values();
        }

        return response()->json($artworks);
    }

    // with pagination
    public function search(Request $request)
    {
        $query = $request->query('q', '');
        $offset = (int) $request->query('offset', 0);
        $limit = (int) $request->query('limit', 10);

        // Build the query to search in name or description
        $artworksQuery = Artwork::query()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%");

        // Get the total count for pagination
        $totalCount = $artworksQuery->count();

        // Fetch the artworks with pagination
        $artworks = $artworksQuery
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Determine if more records are available after these
        $hasMore = $totalCount > ($offset + $limit);

        return response()->json([
            'artworks' => $artworks,
            'has_more' => $hasMore,
        ]);
    }
}