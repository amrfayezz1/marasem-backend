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
        $sortBy = $request->input('sort_by');

        $query = Artwork::query();

        // Filter by category (AND dimension)
        if (!empty($categoryIds)) {
            $query->whereHas('tags.category', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        // Filter by tags (AND dimension)
        if (!empty($tagIds)) {
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        // Filter by location (AND dimension)
        if (!empty($locations)) {
            $query->whereHas('artist.addresses', function ($q) use ($locations) {
                $q->whereIn('city', $locations);
            });
        }

        // Filter by price range using min_price and max_price
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
                    $query->withCount('orderItems') // Count related order items
                        ->orderBy('order_items_count', 'desc'); // Sort by the count in descending order
                    break;

                case 'most_liked':
                    $query->orderBy('likes_count', 'desc'); // Assuming 'likes_count' is a column in the artworks table
                    break;

                case 'price_low_to_high':
                    $query->orderBy('min_price', 'asc'); // Use min_price for low to high sorting
                    break;

                case 'price_high_to_low':
                    $query->orderBy('max_price', 'desc'); // Use max_price for high to low sorting
                    break;
            }
        }
        $artworks = $query->get();
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