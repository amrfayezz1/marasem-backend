<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Language;
use App\Models\Artwork;
use App\Models\Tag;
use App\Models\CustomizedOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\PromoCode;
use App\Models\CreditTransaction;
use App\Models\ArtistPickupLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArtistController extends Controller
{
    /**
     * @OA\Get(
     *     path="/artists",
     *     summary="Get a list of artists",
     *     tags={"Artists"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by artist's name",
     *         @OA\Schema(type="string", example="John Doe")
     *     ),
     *     @OA\Parameter(
     *         name="tags",
     *         in="query",
     *         required=false,
     *         description="Filter by tag IDs",
     *         @OA\Schema(type="array", @OA\Items(type="integer", example=1))
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Sort by specific field",
     *         @OA\Schema(type="string", enum={"followers", "appreciations", "profile_views"}, example="followers")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of artists per page",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of artists retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ArtistDetail")),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     )
     * )
     */

    public function getArtists(Request $request)
    {
        $query = User::whereHas('artistDetails', function ($q) {
            $q->where('completed', 1);
        })->with([
                    'artistDetails.translations' => function ($query) {
                        $user = auth('sanctum')->user();
                        $preferredLanguageId = $user ? $user->preferred_language : Language::where('code', request()->cookie('locale', 'en'))->first()->id;
                        $query->where('language_id', $preferredLanguageId);
                    },
                    'translations' => function ($query) {
                        $user = auth('sanctum')->user();
                        $preferredLanguageId = $user ? $user->preferred_language : Language::where('code', request()->cookie('locale', 'en'))->first()->id;
                        $query->where('language_id', $preferredLanguageId);
                    }
                ]);

        // Search by name
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('last_name', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Filter by tags
        if ($request->has('tags')) {
            $tagIds = $request->input('tags');
            $query->whereHas('artworks.tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        // Sort options
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'followers':
                    $query->withCount('followers')->orderByDesc('followers_count');
                    break;
                case 'appreciations':
                    $query->orderByDesc('appreciations_count');
                    break;
                case 'profile_views':
                    $query->orderByDesc('profile_views');
                    break;
                default:
                    break;
            }
        }

        // Fetch paginated or all results
        $perPage = $request->input('per_page', 12);
        $artists = $perPage == -1 ? $query->get() : $query->paginate($perPage);

        // Add additional details and translations to response
        $user = Auth::user();
        $artists->map(function ($artist) use ($user) {
            // Translate artist names
            $translation = $artist->translations->first();
            \Log::info($translation);
            $artist->first_name = $translation->first_name ?? $artist->first_name;
            $artist->last_name = $translation->last_name ?? $artist->last_name;

            $detailsTranslation = $artist->artistDetails->translations->first();
            $artist->artistDetails->summary = $detailsTranslation->summary ?? $artist->artistDetails->summary;

            $artist->most_recent_artworks = $artist->artworks()->latest()->take(3)->pluck('photos');
            $artist->is_followed = $user ? $artist->getIsFollowedAttribute() : false;
            $artist->city = $artist->addresses->first()->city ?? null;
            $artist->zone = $artist->addresses->first()->zone ?? null;
            $artist->followers_count = $artist->followers()->count();

        });

        return response()->json($artists);
    }

    /**
     * @OA\Post(
     *     path="/artists/{id}/follow",
     *     summary="Follow an artist",
     *     tags={"Artists"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the artist to follow",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Artist followed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Artist followed successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Artist not found"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot follow the artist"
     *     )
     * )
     */

    public function followArtist($artistId)
    {
        $user = Auth::user();

        // Check if the artist exists
        $artist = User::where('id', $artistId)->whereHas('artistDetails', function ($q) {
            $q->where('completed', 1);
        })->first();

        if (!$artist) {
            return response()->json(['error' => 'Artist not found or not eligible for following.'], 404);
        }

        // Prevent self-follow
        if ($artist->id === $user->id) {
            return response()->json(['error' => 'You cannot follow yourself.'], 400);
        }

        // Check if already followed
        if ($user->follows()->where('artist_id', $artist->id)->exists()) {
            return response()->json(['message' => 'You are already following this artist.'], 200);
        }

        // Follow the artist
        $user->follows()->attach($artist->id);

        return response()->json(['message' => 'Artist followed successfully.']);
    }

    /**
     * @OA\Post(
     *     path="/artists/{id}/unfollow",
     *     summary="Unfollow an artist",
     *     tags={"Artists"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the artist to unfollow",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Artist unfollowed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Artist unfollowed successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Artist not found"
     *     )
     * )
     */

    public function unfollowArtist($artistId)
    {
        $user = Auth::user();

        // Check if the artist exists
        $artist = User::where('id', $artistId)->whereHas('artistDetails', function ($q) {
            $q->where('completed', 1);
        })->first();

        if (!$artist) {
            return response()->json(['error' => 'Artist not found or not eligible for unfollowing.'], 404);
        }

        // Check if already not following
        if (!$user->follows()->where('artist_id', $artist->id)->exists()) {
            return response()->json(['message' => 'You are not following this artist.'], 200);
        }

        // Unfollow the artist
        $user->follows()->detach($artist->id);

        return response()->json(['message' => 'Artist unfollowed successfully.']);
    }

    /**
     * @OA\Get(
     *     path="/artists/{id}",
     *     summary="Get detailed information about an artist",
     *     tags={"Artists"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the artist",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Artist details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="artist", ref="#/components/schemas/ArtistDetail"),
     *             @OA\Property(property="artworks", type="array", @OA\Items(ref="#/components/schemas/Artwork")),
     *             @OA\Property(property="liked_artworks", type="array", @OA\Items(ref="#/components/schemas/Artwork")),
     *             @OA\Property(property="sold_out_artworks", type="array", @OA\Items(ref="#/components/schemas/Artwork")),
     *             @OA\Property(property="artwork_views", type="integer", example=123),
     *             @OA\Property(property="appreciations", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Artist not found"
     *     )
     * )
     */

    public function getArtistData(Request $request, $id)
    {
        $user = auth('sanctum')->user();
        $preferredLanguageId = $user ? $user->preferred_language : Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        // Fetch artist and their related details
        $artist = User::withCount(['followers', 'artworks as artworks_count'])
            ->with([
                'artistDetails.translations' => function ($query) use ($preferredLanguageId) {
                    $query->where('language_id', $preferredLanguageId);
                },
                'translations' => function ($query) use ($preferredLanguageId) {
                    $query->where('language_id', $preferredLanguageId);
                }
            ])
            ->whereHas('artistDetails', function ($q) {
                $q->where('completed', 1);
            })
            ->find($id);

        if (!$artist) {
            return response()->json(['message' => 'Artist not found'], 404);
        }

        // Increment profile views (project views) for unique visitors
        $ip = $request->ip();
        $uniqueKey = "artist_view_{$id}_{$ip}";

        if (!cache()->has($uniqueKey)) {
            cache()->put($uniqueKey, true, 86400); // Cache for 1 day
            $artist->artistDetails->increment('profile_views');
        }

        // Translate artist names
        $translation = $artist->translations->first();
        $artist->first_name = $translation->first_name ?? $artist->first_name;
        $artist->last_name = $translation->last_name ?? $artist->last_name;

        // Translate artist details summary
        $detailsTranslation = $artist->artistDetails->translations->first();
        $artist->artistDetails->summary = $detailsTranslation->summary ?? $artist->artistDetails->summary;

        // Fetch artist's artworks
        $artworks = Artwork::with([
            'artist.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            }
        ])
            ->withCount('likes')
            ->where('artist_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Translate artworks
        $artworks->map(function ($artwork) use ($preferredLanguageId) {
            $artworkTranslation = $artwork->translations->first();
            $artwork->name = $artworkTranslation->name ?? $artwork->name;
            $artwork->art_type = $artworkTranslation->art_type ?? $artwork->art_type;
            $artwork->description = $artworkTranslation->description ?? $artwork->description;
        });

        $artworkViews = Artwork::where('artist_id', $id)->sum('views_count');
        $appreciations = Artwork::where('artist_id', $id)->sum('likes_count');

        // Fetch artist's liked artworks
        $likedArtworks = Artwork::whereHas('likes', function ($q) use ($id) {
            $q->where('user_id', $id);
        })
            ->withCount('likes')
            ->get();

        // Translate liked artworks
        $likedArtworks->map(function ($artwork) use ($preferredLanguageId) {
            $artworkTranslation = $artwork->translations->first();
            $artwork->name = $artworkTranslation->name ?? $artwork->name;
            $artwork->art_type = $artworkTranslation->art_type ?? $artwork->art_type;
            $artwork->description = $artworkTranslation->description ?? $artwork->description;
        });

        // Fetch artist's sold-out artworks
        $soldOutArtworks = Artwork::where('artist_id', $id)
            ->where('artwork_status', 'sold_out')
            ->withCount('likes')
            ->get();

        // Translate sold-out artworks
        $soldOutArtworks->map(function ($artwork) use ($preferredLanguageId) {
            $artworkTranslation = $artwork->translations->first();
            $artwork->name = $artworkTranslation->name ?? $artwork->name;
            $artwork->art_type = $artworkTranslation->art_type ?? $artwork->art_type;
            $artwork->description = $artworkTranslation->description ?? $artwork->description;
        });

        $artist->is_followed = $user ? $artist->isFollowedBy($user) : false;

        return response()->json([
            'artist' => $artist,
            'artworks' => $artworks,
            'liked_artworks' => $likedArtworks,
            'sold_out_artworks' => $soldOutArtworks,
            'artwork_views' => $artworkViews,
            'appreciations' => $appreciations,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/customized-orders/{id}/respond",
     *     summary="Respond to a customized order",
     *     tags={"Artists"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the customized order",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"action"},
     *             @OA\Property(property="action", type="string", enum={"accept", "reject"}, example="accept")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Response to the customized order",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Customized order accepted successfully."),
     *             @OA\Property(property="order", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customized order not found"
     *     )
     * )
     */

    public function respondToCustomizedOrder(Request $request, $id)
    {
        $artist = Auth::user();

        if (!$artist || !$artist->is_artist) {
            return response()->json(['error' => 'Only artists can manage customized orders.'], 403);
        }

        $customizedOrder = CustomizedOrder::with('artwork')->find($id);

        if (!$customizedOrder) {
            return response()->json(['error' => 'Customized order not found.'], 404);
        }

        if ($customizedOrder->artwork->artist_id !== $artist->id) {
            return response()->json(['error' => 'You are not authorized to manage this order.'], 403);
        }

        $validated = $request->validate([
            'action' => 'required|in:accept,reject', // Accept or reject
        ]);

        if ($validated['action'] === 'accept') {
            // Mark the customized order as accepted
            $customizedOrder->update(['status' => 'accepted']);

            // Create a new order
            $order = Order::create([
                'user_id' => $customizedOrder->user_id,
                'address_id' => $customizedOrder->address_id,
                'total_amount' => $customizedOrder->offering_price,
                'status' => 'pending',
                'original_total' => $customizedOrder->offering_price,
            ]);

            // Add the customized order as an order item
            OrderItem::create([
                'order_id' => $order->id,
                'artwork_id' => $customizedOrder->artwork_id,
                'size' => $customizedOrder->size,
                'quantity' => 1, // Assuming one customized order per item
                'price' => $customizedOrder->offering_price,
            ]);

            return response()->json([
                'message' => 'Customized order accepted successfully.',
                'order' => $order,
            ]);
        }

        if ($validated['action'] === 'reject') {
            // Delete the customized order
            $customizedOrder->delete();

            return response()->json(['message' => 'Customized order rejected and deleted.']);
        }
    }

    /**
     * @OA\Post(
     *     path="/artist/general-info",
     *     summary="Update the general information of the artist",
     *     tags={"Artist"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"first_name", "last_name", "country_code", "phone", "email"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="country_code", type="string", example="+1"),
     *             @OA\Property(property="phone", type="string", example="123456789"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", nullable=true, example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", nullable=true, example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="General information updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="General information updated successfully."),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     )
     * )
     */

    public function updateGeneralInfo(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->is_artist) {
            return response()->json(['error' => 'Only artists can update this information.'], 403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'country_code' => 'required|string|max:10',
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        if (!$request->password) {
            $validated['password'] = null;
        }

        // Update user details
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'country_code' => $validated['country_code'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'password' => $validated['password'] ? bcrypt($validated['password']) : $user->password,
        ]);

        return response()->json(['message' => 'General information updated successfully.', 'user' => $user]);
    }

    /**
     * @OA\Post(
     *     path="/artist/about-me",
     *     summary="Update the artist's About Me section",
     *     tags={"Artist"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="summary", type="string", nullable=true, example="I am a passionate artist."),
     *             @OA\Property(property="social_media_link", type="string", format="url", nullable=true, example="https://instagram.com/johndoe"),
     *             @OA\Property(property="portfolio_link", type="string", format="url", nullable=true, example="https://portfolio.com/johndoe"),
     *             @OA\Property(property="website_link", type="string", format="url", nullable=true, example="https://johndoe.com"),
     *             @OA\Property(property="other_link", type="string", format="url", nullable=true, example="https://other.com/johndoe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="About Me updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="About me updated successfully."),
     *             @OA\Property(property="artistDetails", ref="#/components/schemas/ArtistDetail")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     )
     * )
     */

    public function updateAboutMe(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->is_artist) {
            return response()->json(['error' => 'Only artists can update this information.'], 403);
        }

        $validated = $request->validate([
            'summary' => 'nullable|string',
            'social_media_link' => 'nullable|url',
            'portfolio_link' => 'nullable|url',
            'website_link' => 'nullable|url',
            'other_link' => 'nullable|url',
        ]);

        $artistDetails = $user->artistDetails;

        if (!$artistDetails) {
            return response()->json(['error' => 'Artist details not found.'], 404);
        }

        $artistDetails->update($validated);

        return response()->json(['message' => 'About me updated successfully.', 'artistDetails' => $artistDetails]);
    }

    /**
     * @OA\Post(
     *     path="/artist/pickup-location",
     *     summary="Update the artist's pickup location",
     *     tags={"Artist"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"city", "address"},
     *             @OA\Property(property="city", type="string", example="Cairo"),
     *             @OA\Property(property="zone", type="string", nullable=true, example="Downtown"),
     *             @OA\Property(property="address", type="string", example="123 Main St")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pickup location updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Pick-up location added successfully."),
     *             @OA\Property(property="pickup_location", ref="#/components/schemas/ArtistPickupLocation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     )
     * )
     */

    public function updatePickupLocation(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->is_artist) {
            return response()->json(['error' => 'Only artists can update this information.'], 403);
        }

        $validated = $request->validate([
            'city' => 'required|string|max:255',
            'zone' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
        ]);

        // remove previous location for this artist
        ArtistPickupLocation::where('artist_id', $user->id)->delete();

        // Create or update the pick-up location
        $pickupLocation = ArtistPickupLocation::create([
            'artist_id' => $user->id,
            'city' => $validated['city'],
            'zone' => $validated['zone'] ?? null,
            'address' => $validated['address'],
        ]);

        return response()->json([
            'message' => 'Pick-up location added successfully.',
            'pickup_location' => $pickupLocation,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/artist/focus",
     *     summary="Update the artist's focus tags",
     *     tags={"Artist"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"tags"},
     *             @OA\Property(property="tags", type="array", @OA\Items(type="integer", example=1))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Focus updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Focus updated successfully."),
     *             @OA\Property(property="tags", type="array", @OA\Items(ref="#/components/schemas/Tag"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     )
     * )
     */

    public function updateFocus(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->is_artist) {
            return response()->json(['error' => 'Only artists can update this information.'], 403);
        }

        $validated = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'exists:tags,id',
        ]);

        // Sync tags with the user
        $user->tags()->sync($validated['tags']);

        return response()->json(['message' => 'Focus updated successfully.', 'tags' => $user->tags]);
    }

    /**
     * @OA\Get(
     *     path="/artist/focus",
     *     summary="Get the artist's focus tags and available categories",
     *     tags={"Artist"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Focus and categories retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="categories", type="array", @OA\Items(ref="#/components/schemas/Category")),
     *             @OA\Property(property="user_tags", type="array", @OA\Items(type="integer", example=1))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     )
     * )
     */

    public function getFocus()
    {
        $user = Auth::user();

        if (!$user || !$user->is_artist) {
            return response()->json(['error' => 'Only artists can access this information.'], 403);
        }

        $preferredLanguageId = $user->preferred_language ?? Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        // Fetch categories and their tags with translations
        $categories = Category::with([
            'tags.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            }
        ])->get();

        // Translate categories and tags
        $categories->map(function ($category) {
            $translation = $category->translations->first();
            $category->name = $translation->name ?? $category->name;

            $category->tags->map(function ($tag) {
                $tagTranslation = $tag->translations->first();
                $tag->name = $tagTranslation->name ?? $tag->name;
            });
        });

        // User followed tags
        $userTags = $user->tags()->pluck('tags.id')->toArray();

        return response()->json(['categories' => $categories, 'user_tags' => $userTags]);
    }

    /**
     * @OA\Post(
     *     path="/artist/cover-image",
     *     summary="Update the artist's cover image",
     *     tags={"Artist"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="cover_img", type="string", format="binary", description="Image file")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cover image updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Cover image updated successfully."),
     *             @OA\Property(property="cover_img", type="string", format="url", example="https://example.com/cover.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     )
     * )
     */

    public function updateCoverImage(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'cover_img' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Delete old cover image if exists
        if ($user->cover_img) {
            $relativePath = str_replace(asset('storage/'), '', $user->cover_img);
            \Storage::disk('public')->delete($relativePath);
        }

        // Upload new cover image
        $path = $request->file('cover_img')->store('cover_images', 'public');
        $fullPath = asset('storage/' . $path);

        // Update user record
        $user->update(['cover_img' => $fullPath]);

        return response()->json(['message' => 'Cover image updated successfully.', 'cover_img' => $fullPath]);
    }

    /**
     * @OA\Get(
     *     path="/artist/my-orders",
     *     summary="Get the list of orders associated with the artist",
     *     tags={"Orders"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Orders retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="standard_orders", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="order_id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Order #1"),
     *                 @OA\Property(property="date", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="artworks_count", type="integer", example=2),
     *                 @OA\Property(property="total_price", type="number", example=500.75),
     *                 @OA\Property(property="status", type="string", example="pending")
     *             )),
     *             @OA\Property(property="customized_orders", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="order_id", type="integer", example=5),
     *                 @OA\Property(property="title", type="string", example="Customized Order #5"),
     *                 @OA\Property(property="date", type="string", format="date", example="2024-01-01"),
     *                 @OA\Property(property="artwork", type="string", example="Custom Portrait"),
     *                 @OA\Property(property="desired_size", type="string", example="24x36"),
     *                 @OA\Property(property="offering_price", type="number", example=750.50),
     *                 @OA\Property(property="status", type="string", example="accepted")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     )
     * )
     */

    public function viewOrders(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->is_artist) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $preferredLanguageId = $user->preferred_language ?? Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        // Fetch standard orders containing the artist's artworks
        $standardOrders = Order::with([
            'items.artwork.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'address',
            'payments'
        ])
            ->whereHas('items.artwork', function ($query) use ($user) {
                $query->where('artist_id', $user->id);
            })
            ->get();

        $standardOrderSummaries = $standardOrders->map(function ($order) use ($preferredLanguageId) {
            return [
                'order_id' => $order->id,
                'title' => "Order #{$order->id}",
                'date' => $order->created_at->format('Y-m-d'),
                'artworks_count' => $order->items->count(),
                'total_price' => $order->total_amount,
                'status' => $order->order_status,
                'items' => $order->items->map(function ($item) use ($preferredLanguageId) {
                    $artworkTranslation = $item->artwork->translations->first();
                    return [
                        'artwork_name' => $artworkTranslation->name ?? $item->artwork->name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ];
                })
            ];
        });

        // Fetch customized orders
        $customizedOrders = CustomizedOrder::with([
            'artwork.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'address'
        ])
            ->whereHas('artwork', function ($query) use ($user) {
                $query->where('artist_id', $user->id);
            })
            ->get();

        $customizedOrderSummaries = $customizedOrders->map(function ($customOrder) use ($preferredLanguageId) {
            $artworkTranslation = $customOrder->artwork->translations->first();
            return [
                'order_id' => $customOrder->id,
                'title' => "Customized Order #{$customOrder->id}",
                'date' => $customOrder->created_at->format('Y-m-d'),
                'artwork' => $artworkTranslation->name ?? $customOrder->artwork->name ?? 'N/A',
                'desired_size' => $customOrder->desired_size,
                'offering_price' => $customOrder->offering_price,
                'status' => $customOrder->status,
            ];
        });

        return response()->json([
            'standard_orders' => $standardOrderSummaries,
            'customized_orders' => $customizedOrderSummaries,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/artist/customized-orders/{id}",
     *     summary="Get details of a specific customized order",
     *     tags={"Orders"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the customized order",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customized order details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="customized_order_details", type="object",
     *                 @OA\Property(property="title", type="string", example="Customized Order #5"),
     *                 @OA\Property(property="order_id", type="integer", example=5),
     *                 @OA\Property(property="date", type="string", format="datetime", example="2024-01-01 12:00:00"),
     *                 @OA\Property(property="artwork_name", type="string", example="Custom Portrait"),
     *                 @OA\Property(property="desired_size", type="string", example="24x36"),
     *                 @OA\Property(property="offering_price", type="number", example=750.50),
     *                 @OA\Property(property="status", type="string", example="accepted"),
     *                 @OA\Property(property="description", type="string", example="Special request for custom colors."),
     *                 @OA\Property(property="address", ref="#/components/schemas/Address")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customized order not found"
     *     )
     * )
     */

    public function viewCustomizedOrderDetails($id)
    {
        $user = Auth::user();

        if (!$user || !$user->is_artist) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $preferredLanguageId = $user->preferred_language ?? Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        // Fetch the customized order
        $customOrder = CustomizedOrder::with([
            'artwork.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'address'
        ])
            ->whereHas('artwork', function ($query) use ($user) {
                $query->where('artist_id', $user->id);
            })
            ->find($id);

        if (!$customOrder) {
            return response()->json(['error' => 'Customized order not found.'], 404);
        }

        // Artwork translations
        $artworkTranslation = $customOrder->artwork->translations->first();
        $customOrder->artwork->name = $artworkTranslation->name ?? $customOrder->artwork->name;

        // Customized order description translation
        $descriptionTranslation = $customOrder->translations->first();
        $customOrder->description = $descriptionTranslation->description ?? $customOrder->description;

        $details = [
            'title' => "Customized Order #{$customOrder->id}",
            'order_id' => $customOrder->id,
            'date' => $customOrder->created_at->format('Y-m-d H:i:s'),
            'artwork_name' => $customOrder->artwork->name,
            'desired_size' => $customOrder->desired_size,
            'offering_price' => $customOrder->offering_price,
            'status' => $customOrder->status,
            'description' => $customOrder->description,
            'address' => $customOrder->address,
        ];

        return response()->json(['customized_order_details' => $details]);
    }

    /**
     * @OA\Get(
     *     path="/artist/my-orders/{id}",
     *     summary="Get details of a specific standard order",
     *     tags={"Orders"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the order",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="order_details", type="object",
     *                 @OA\Property(property="title", type="string", example="Order #1"),
     *                 @OA\Property(property="order_id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="date", type="string", format="datetime", example="2024-01-01 12:00:00"),
     *                 @OA\Property(property="shipment_address", ref="#/components/schemas/Address"),
     *                 @OA\Property(property="payment_method", type="string", example="cash"),
     *                 @OA\Property(property="invoice_link", type="string", format="url", nullable=true, example="https://example.com/invoice.pdf"),
     *                 @OA\Property(property="price_breakdown", type="object",
     *                     @OA\Property(property="artworks_total", type="number", example=750.50),
     *                     @OA\Property(property="marasem_credit_used", type="number", example=50.00),
     *                     @OA\Property(property="promo_discount", type="number", example=25.00),
     *                     @OA\Property(property="promo_type", type="string", example="fixed"),
     *                     @OA\Property(property="final_total", type="number", example=675.50)
     *                 ),
     *                 @OA\Property(property="artworks", type="array", @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="image", type="string", format="url", example="https://example.com/image.jpg"),
     *                     @OA\Property(property="title", type="string", example="Sunset Painting"),
     *                     @OA\Property(property="type", type="string", example="Painting"),
     *                     @OA\Property(property="size", type="string", example="24x36"),
     *                     @OA\Property(property="price", type="number", example=250.50),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(property="status", type="string", example="available"),
     *                     @OA\Property(property="artist", ref="#/components/schemas/User")
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     )
     * )
     */

    public function viewOrderDetails($id)
    {
        $user = Auth::user();

        if (!$user || !$user->is_artist) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $preferredLanguageId = $user->preferred_language ?? Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        // Fetch the order details
        $order = Order::with([
            'items.artwork.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'items.artwork.artist.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'address',
            'payments',
            'invoice'
        ])
            ->whereHas('items.artwork', function ($query) use ($user) {
                $query->where('artist_id', $user->id);
            })
            ->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found.'], 404);
        }

        // Prepare price quotation breakdown
        $priceBreakdown = [
            'artworks_total' => $order->items->reduce(fn($carry, $item) => $carry + ($item->price * $item->quantity), 0),
            'marasem_credit_used' => $order->marasem_credit_used,
            'promo_discount' => $order->promo_code_id ? PromoCode::find($order->promo_code_id)->discount_value : 0,
            'promo_type' => $order->promo_code_id ? PromoCode::find($order->promo_code_id)->discount_type : null,
            'final_total' => $order->total_amount,
        ];

        // Format response
        $details = [
            'title' => "Order #{$order->id}",
            'order_id' => $order->id,
            'status' => $order->order_status,
            'date' => $order->created_at->format('Y-m-d H:i:s'),
            'shipment_address' => $order->address,
            'payment_method' => $order->payments->first()->method ?? 'Unknown',
            'invoice_link' => $order->invoice->path ?? null,
            'price_breakdown' => $priceBreakdown,
            'artworks' => $order->items->map(function ($item) use ($preferredLanguageId) {
                $artwork = $item->artwork;

                // Artwork translations
                $artworkTranslation = $artwork->translations->first();
                $artwork->name = $artworkTranslation->name ?? $artwork->name;
                $artwork->art_type = $artworkTranslation->art_type ?? $artwork->art_type;

                // Artist translations
                $artistTranslation = $artwork->artist->translations->first();
                $artwork->artist->first_name = $artistTranslation->first_name ?? $artwork->artist->first_name;
                $artwork->artist->last_name = $artistTranslation->last_name ?? $artwork->artist->last_name;

                return [
                    'image' => $artwork->photos[0] ?? null,
                    'title' => $artwork->name,
                    'type' => $artwork->art_type,
                    'size' => $item->size,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'status' => $artwork->artwork_status,
                    'artist' => $artwork->artist,
                ];
            }),
        ];

        return response()->json(['order_details' => $details]);
    }

    /**
     * @OA\Get(
     *     path="/artist/available-balance",
     *     summary="Get the artist's available Marasem Credit balance and transactions",
     *     tags={"Artist"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Balance and transactions retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="marasem_credit", type="number", example=500.00),
     *             @OA\Property(property="transactions", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="type", type="string", example="debit"),
     *                 @OA\Property(property="amount", type="number", example=50.00),
     *                 @OA\Property(property="reference", type="string", example="Order #1"),
     *                 @OA\Property(property="description", type="string", example="Payment for Order #1"),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2024-01-01 12:00:00")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     )
     * )
     */

    public function getAvailableBalance()
    {
        $user = Auth::user();

        if (!$user || !$user->is_artist) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Fetch Marasem Credit balance
        $marasemCredit = $user->marasem_credit;

        // Fetch transactions
        $transactions = CreditTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(['type', 'amount', 'reference', 'description', 'created_at']);

        return response()->json([
            'marasem_credit' => $marasemCredit,
            'transactions' => $transactions,
        ]);
    }
}
