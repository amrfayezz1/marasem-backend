<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\Artwork;
use App\Models\CustomizedOrder;
use App\Models\Collection;
use App\Models\Order;
use App\Models\CreditTransaction;
use App\Models\PromoCode;

class UserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/change-currency",
     *     summary="Change the user's preferred currency",
     *     tags={"User"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"currency"},
     *             @OA\Property(
     *                 property="currency",
     *                 type="string",
     *                 example="USD",
     *                 description="The 3-letter currency code to set as preferred currency."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Currency updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Currency updated successfully."),
     *             @OA\Property(property="preferred_currency", type="string", example="USD", description="The updated preferred currency.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="You must be logged in to change currency.")
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

    public function changeCurrency(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'currency' => 'required|string|size:3',
        ]);

        // Update the preferred currency
        $user->update(['preferred_currency' => $validated['currency']]);

        return response()->json([
            'message' => 'Currency updated successfully.',
            'preferred_currency' => $user->preferred_currency,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/artist/profile",
     *     summary="Fetch the logged-in artist's profile and insights",
     *     tags={"Artist"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Artist profile fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="artist", ref="#/components/schemas/User"),
     *             @OA\Property(property="artworks", type="array", @OA\Items(ref="#/components/schemas/Artwork")),
     *             @OA\Property(property="drafts", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="to_do", type="object"),
     *             @OA\Property(property="liked_artworks", type="array", @OA\Items(ref="#/components/schemas/Artwork")),
     *             @OA\Property(property="insights", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Artist not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Artist not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access"
     *     )
     * )
     */

    public function getArtistProfile(Request $request)
    {
        $id = Auth::id();
        // Fetch the artist
        $artist = User::withCount(['followers', 'follows'])
            ->with('artistDetails')
            ->whereHas('artistDetails', function ($q) {
                $q->where('completed', 1);
            })
            ->find($id);

        if (!$artist) {
            return response()->json(['message' => 'Artist not found'], 404);
        }

        // Fetch insights
        $totalSales = OrderItem::whereHas('artwork', function ($q) use ($id) {
            $q->where('artist_id', $id);
        })->sum(DB::raw('price * quantity'));

        $totalSoldArtworks = OrderItem::whereHas('artwork', function ($q) use ($id) {
            $q->where('artist_id', $id);
        })->sum('quantity');

        $artworkViews = Artwork::where('artist_id', $id)->sum('views_count');

        $appreciations = Artwork::where('artist_id', $id)->sum('likes_count');

        // Fetch artworks
        $artworks = Artwork::withCount('likes')
            ->where('artist_id', $id)
            ->where('artwork_status', 'ready to ship')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($artwork) {
                return [
                    'id' => $artwork->id,
                    'name' => $artwork->name,
                    'views' => $artwork->views_count,
                    'appreciations' => $artwork->likes_count,
                    'status' => $artwork->artwork_status,
                ];
            });
        $drafts = Artwork::where('artist_id', $id)
            ->where('artwork_status', 'draft')
            ->get()
            ->map(function ($draft) {
                return [
                    'id' => $draft->id,
                    'name' => $draft->name,
                    'description' => $draft->description,
                    'created_at' => $draft->created_at,
                ];
            });

        // Fetch ToDo list (customized and ordered items)
        $toDoList = [
            'customized_orders' => CustomizedOrder::whereHas('artwork', function ($q) use ($id) {
                $q->where('artist_id', $id);
            })->get(),
            'ordered_items' => OrderItem::whereHas('artwork', function ($q) use ($id) {
                $q->where('artist_id', $id);
            })
                ->with('order.user')
                ->get()
                ->map(function ($item) {
                    return [
                        'artwork_name' => $item->artwork->name,
                        'username' => $item->order->user->first_name . ' ' . $item->order->user->last_name,
                        'size' => $item->size,
                        'price' => $item->price,
                        'order_date' => $item->order->created_at,
                        'status' => $item->order->status,
                    ];
                }),
        ];

        // Fetch artist's liked artworks
        $likedArtworks = Artwork::whereHas('likes', function ($q) use ($id) {
            $q->where('user_id', $id);
        })->withCount('likes')->get();

        return response()->json([
            'artist' => $artist,
            'liked_artworks' => $likedArtworks,
            'artworks' => $artworks,
            'drafts' => $drafts,
            'to_do' => $toDoList,
            'insights' => [
                'total_sales' => $totalSales,
                'total_sold_artworks' => $totalSoldArtworks,
                'to_do_count' => count($toDoList['customized_orders']) + count($toDoList['ordered_items']),
                'profile_views' => $artist->artistDetails->profile_views,
                'project_views' => $artworkViews,
                'appreciations' => $appreciations,
                'followers' => $artist->followers_count ?? 0,
                'following' => $artist->following_count ?? 0,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/user/profile-picture",
     *     summary="Update the user's profile picture",
     *     tags={"User"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="profile_picture", type="string", format="binary", description="Image file for the profile picture.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile picture updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Profile picture updated successfully."),
     *             @OA\Property(property="profile_picture", type="string", example="https://example.com/profile.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */

    public function updateProfilePicture(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Delete old profile picture if exists
        if ($user->profile_picture) {
            $relativePath = str_replace(asset('storage/'), '', $user->profile_picture);
            \Storage::disk('public')->delete($relativePath);
        }

        // Upload new profile picture
        $path = $request->file('profile_picture')->store('profile_pictures', 'public');
        $fullPath = asset('storage/' . $path);

        // Update user record
        $user->update(['profile_picture' => $fullPath]);

        return response()->json(['message' => 'Profile picture updated successfully.', 'profile_picture' => $fullPath]);
    }

    /**
     * @OA\Get(
     *     path="/user/info",
     *     summary="Fetch the logged-in user's profile information",
     *     tags={"User"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User info fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="followed_artists", type="array", @OA\Items(ref="#/components/schemas/User")),
     *             @OA\Property(property="addresses", type="array", @OA\Items(ref="#/components/schemas/Address")),
     *             @OA\Property(property="orders", type="array", @OA\Items(ref="#/components/schemas/Order")),
     *             @OA\Property(property="followed_collections", type="array", @OA\Items(ref="#/components/schemas/Collection")),
     *             @OA\Property(property="credit_transactions", type="array", @OA\Items(ref="#/components/schemas/CreditTransaction"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access"
     *     )
     * )
     */

    public function getUserInfo(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'You must be logged in to access this information.'], 401);
        }

        // Fetch user's followed artists
        $followedArtists = $user->follows()
            ->withCount(['artworks', 'followers'])
            ->with(['artistDetails'])
            ->get()
            ->map(function ($artist) {
                return [
                    'id' => $artist->id,
                    'name' => $artist->first_name . ' ' . $artist->last_name,
                    'profile_picture' => $artist->profile_picture,
                    'artworks_count' => $artist->artworks_count,
                    'followers_count' => $artist->followers_count,
                    'is_followed' => true,
                ];
            });

        // Fetch user's addresses
        $addresses = $user->addresses;

        // Fetch user's orders with details
        $orders = Order::where('user_id', $user->id)
            ->with(['items.artwork', 'payments', 'invoice', 'address'])
            ->orderBy('created_at', 'desc')
            ->get();

        $ordersData = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'total_amount' => $order->total_amount,
                'status' => $order->status,
                'order_status' => $order->order_status ?? 'pending',
                'created_at' => $order->created_at->toDateTimeString(),
                'items' => $order->items->map(function ($item) {
                    return [
                        'artwork_name' => $item->artwork->name,
                        'size' => $item->size,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ];
                }),
                'invoice' => $order->invoice ? [
                    'path' => $order->invoice->path,
                    'amount' => $order->invoice->amount,
                ] : null,
                'address' => $order->address,
            ];
        });

        // Fetch user's followed collections
        $followedCollections = Collection::whereHas('followers', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->get(['id', 'title', 'followers']);

        // Fetch Marasem credit and transactions
        $marasemCredit = $user->marasem_credit;
        $creditTransactions = CreditTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'type', 'amount', 'reference', 'description', 'created_at', 'expiry_date']);

        // Compile all data
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country_code' => $user->country_code,
                'profile_picture' => $user->profile_picture,
                'preferred_currency' => $user->preferred_currency,
                'marasem_credit' => $marasemCredit,
            ],
            'followed_artists' => $followedArtists,
            'addresses' => $addresses,
            'orders' => $ordersData,
            'followed_collections' => $followedCollections,
            'credit_transactions' => $creditTransactions,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/user/update",
     *     summary="Update the logged-in user's profile information",
     *     tags={"User"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"first_name", "last_name", "email", "country_code", "phone"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *             @OA\Property(property="country_code", type="string", example="+1"),
     *             @OA\Property(property="phone", type="string", example="123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Profile updated successfully."),
     *             @OA\Property(property="user", type="object", @OA\Property(property="first_name", type="string", example="John"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */

    public function updateUserInfo(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'You must be logged in to update your profile.'], 401);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'country_code' => 'required|string|max:5',
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'country_code' => $user->country_code,
                'phone' => $user->phone,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/user/orders",
     *     summary="Retrieve the list of orders for the logged-in user",
     *     tags={"Orders"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of standard and customized orders retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="standard_orders",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="order_id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Order #1"),
     *                     @OA\Property(property="date", type="string", example="2023-12-20"),
     *                     @OA\Property(property="artworks_count", type="integer", example=2),
     *                     @OA\Property(property="total_price", type="number", example=250.75),
     *                     @OA\Property(property="status", type="string", example="completed")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="customized_orders",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="order_id", type="integer", example=5),
     *                     @OA\Property(property="title", type="string", example="Customized Order #5"),
     *                     @OA\Property(property="date", type="string", example="2023-12-21"),
     *                     @OA\Property(property="artwork", type="string", example="Sunset Painting"),
     *                     @OA\Property(property="desired_size", type="string", example="24x36"),
     *                     @OA\Property(property="offering_price", type="number", example=300.00),
     *                     @OA\Property(property="status", type="string", example="pending")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Unauthorized access.")
     *         )
     *     )
     * )
     */

    public function viewOrders(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Fetch standard orders
        $standardOrders = Order::with(['items.artwork', 'address', 'payments'])
            ->where('user_id', $user->id)
            ->get();

        $standardOrderSummaries = $standardOrders->map(function ($order) {
            return [
                'order_id' => $order->id,
                'title' => "Order #{$order->id}",
                'date' => $order->created_at->format('Y-m-d'),
                'artworks_count' => $order->items->count(),
                'total_price' => $order->total_amount,
                'status' => $order->order_status,
            ];
        });

        // Fetch customized orders
        $customizedOrders = CustomizedOrder::with(['artwork', 'address'])
            ->where('user_id', $user->id)
            ->get();

        $customizedOrderSummaries = $customizedOrders->map(function ($customOrder) {
            return [
                'order_id' => $customOrder->id,
                'title' => "Customized Order #{$customOrder->id}",
                'date' => $customOrder->created_at->format('Y-m-d'),
                'artwork' => $customOrder->artwork->name ?? 'N/A',
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
     *     path="/user/orders/{id}",
     *     summary="Retrieve details of a specific order",
     *     tags={"Orders"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID to fetch details for",
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
     *                 @OA\Property(property="date", type="string", example="2023-12-20 14:32:00"),
     *                 @OA\Property(property="shipment_address", type="object", ref="#/components/schemas/Address"),
     *                 @OA\Property(property="payment_method", type="string", example="cash"),
     *                 @OA\Property(property="invoice_link", type="string", example="https://example.com/invoice.pdf"),
     *                 @OA\Property(property="price_breakdown", type="object",
     *                     @OA\Property(property="artworks_total", type="number", example=250.00),
     *                     @OA\Property(property="marasem_credit_used", type="number", example=10.00),
     *                     @OA\Property(property="promo_discount", type="number", example=20.00),
     *                     @OA\Property(property="promo_type", type="string", example="fixed"),
     *                     @OA\Property(property="final_total", type="number", example=220.00)
     *                 ),
     *                 @OA\Property(property="artworks", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="image", type="string", example="https://example.com/artwork.jpg"),
     *                         @OA\Property(property="title", type="string", example="Abstract Art"),
     *                         @OA\Property(property="type", type="string", example="Painting"),
     *                         @OA\Property(property="size", type="string", example="24x36"),
     *                         @OA\Property(property="price", type="number", example=125.00),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="status", type="string", example="ready to ship")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Order not found.")
     *         )
     *     )
     * )
     */

    public function viewOrderDetails($id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Fetch the order details
        $order = Order::with(['items.artwork', 'address', 'payments', 'invoice'])
            ->where('user_id', $user->id)
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
            'artworks' => $order->items->map(function ($item) {
                return [
                    'image' => $item->artwork->photos[0] ?? null,
                    'title' => $item->artwork->name,
                    'type' => $item->artwork->art_type,
                    'size' => $item->size,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'status' => $item->artwork->artwork_status,
                ];
            }),
        ];

        return response()->json(['order_details' => $details]);
    }

    /**
     * @OA\Get(
     *     path="/user/customized-orders/{id}",
     *     summary="Retrieve details of a specific customized order",
     *     tags={"Orders"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customized order ID to fetch details for",
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
     *                 @OA\Property(property="date", type="string", example="2023-12-21 10:15:00"),
     *                 @OA\Property(property="artwork_name", type="string", example="Sunset Painting"),
     *                 @OA\Property(property="desired_size", type="string", example="24x36"),
     *                 @OA\Property(property="offering_price", type="number", example=300.00),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="description", type="string", example="I want a larger version of this painting."),
     *                 @OA\Property(property="address", type="object", ref="#/components/schemas/Address")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customized order not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Customized order not found.")
     *         )
     *     )
     * )
     */

    public function viewCustomizedOrderDetails($id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Fetch the customized order
        $customOrder = CustomizedOrder::with(['artwork', 'address'])
            ->where('user_id', $user->id)
            ->find($id);

        if (!$customOrder) {
            return response()->json(['error' => 'Customized order not found.'], 404);
        }

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

}
