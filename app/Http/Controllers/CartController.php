<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * @OA\Post(
     *     path="/cart",
     *     summary="Add an item to the cart",
     *     tags={"Cart"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"artwork_id", "size"},
     *             @OA\Property(property="artwork_id", type="integer", example=1, description="ID of the artwork to add"),
     *             @OA\Property(property="size", type="string", example="24x36", description="Selected size of the artwork"),
     *             @OA\Property(property="quantity", type="integer", example=2, description="Quantity of the artwork to add")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item added to cart successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Item added to cart successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="You must be logged in to add to cart.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid size selected"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */

    public function addToCart(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'You must be logged in to add to cart.'], 401);
        }

        $validated = $request->validate([
            'artwork_id' => 'required|exists:artworks,id',
            'size' => 'required|string',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $artwork = Artwork::find($validated['artwork_id']);
        $sizesPrices = json_decode($artwork->sizes_prices, true);

        // If a size is provided, validate it exists in sizes_prices
        $chosenSize = $validated['size'] ?? null;
        if ($chosenSize && !array_key_exists($chosenSize, $sizesPrices)) {
            return response()->json(['error' => 'Invalid size selected.'], 400);
        }

        // If no size chosen, you may decide on a default or require size.
        // Assuming size is required, if empty means error:
        if (!$chosenSize) {
            return response()->json(['error' => 'Size is required.'], 400);
        }

        // Get the price for the chosen size
        $selectedPrice = $sizesPrices[$chosenSize];

        // Check if item already in cart
        $cartItem = CartItem::where('user_id', $user->id)
            ->where('artwork_id', $validated['artwork_id'])
            ->where('size', $chosenSize)
            ->first();

        if ($cartItem) {
            // Update quantity and price if needed
            $cartItem->quantity += $validated['quantity'] ?? 1;
            // Generally, price should remain the same as initially set, but if you always want the latest price, you could update it.
            $cartItem->save();
        } else {
            // Create a new cart item with the chosen price
            CartItem::create([
                'user_id' => $user->id,
                'artwork_id' => $validated['artwork_id'],
                'size' => $chosenSize,
                'quantity' => $validated['quantity'] ?? 1,
                'price' => $selectedPrice
            ]);
        }

        return response()->json(['message' => 'Item added to cart successfully.']);
    }

    /**
     * @OA\Delete(
     *     path="/cart",
     *     summary="Remove an item from the cart",
     *     tags={"Cart"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"artwork_id"},
     *             @OA\Property(property="artwork_id", type="integer", example=1, description="ID of the artwork to remove"),
     *             @OA\Property(property="size", type="string", nullable=true, example="24x36", description="Size of the artwork to remove")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item removed from cart successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Item removed from cart successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Item not found in cart"
     *     )
     * )
     */

    public function removeFromCart(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'You must be logged in to remove items from cart.'], 401);
        }

        $validated = $request->validate([
            'artwork_id' => 'required|exists:artworks,id',
            'size' => 'nullable|string'
        ]);

        // Find the cart item
        $cartItemQuery = CartItem::where('user_id', $user->id)
            ->where('artwork_id', $validated['artwork_id']);

        if (isset($validated['size'])) {
            $cartItemQuery->where('size', $validated['size']);
        }

        $cartItem = $cartItemQuery->first();

        if (!$cartItem) {
            return response()->json(['error' => 'Item not found in cart.'], 404);
        }

        // Remove the item
        $cartItem->delete();

        return response()->json(['message' => 'Item removed from cart successfully.']);
    }

    /**
     * @OA\Get(
     *     path="/cart",
     *     summary="Get all items in the user's cart",
     *     tags={"Cart"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of items in the cart",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="cart_items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="artwork_id", type="integer", example=1),
     *                     @OA\Property(property="size", type="string", example="24x36"),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(property="price", type="number", example=200.50)
     *                 )
     *             ),
     *             @OA\Property(property="items_count", type="integer", example=3),
     *             @OA\Property(property="total", type="number", example=601.50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access"
     *     )
     * )
     */

    public function getCartItems()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'You must be logged in to view your cart.'], 401);
        }

        // Fetch all cart items for the user
        $cartItems = CartItem::with('artwork')
            ->where('user_id', $user->id)
            ->get();

        // Calculate the total
        $total = $cartItems->reduce(function ($carry, $item) {
            return $carry + ($item->price * $item->quantity);
        }, 0);
        $itemsCount = $cartItems->sum('quantity');

        return response()->json([
            'cart_items' => $cartItems,
            'items_count' => $itemsCount,
            'total' => $total
        ]);
    }

    /**
     * @OA\Get(
     *     path="/checkout",
     *     summary="Get checkout data including cart items and addresses",
     *     tags={"Cart"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Checkout data fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="items_count", type="integer", example=3),
     *             @OA\Property(property="total", type="number", example=601.50),
     *             @OA\Property(
     *                 property="addresses",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="city", type="string", example="Cairo"),
     *                     @OA\Property(property="zone", type="string", example="Downtown"),
     *                     @OA\Property(property="address", type="string", example="123 Main St"),
     *                     @OA\Property(property="is_default", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access"
     *     )
     * )
     */

    public function getCheckoutData()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'You must be logged in to proceed to checkout.'], 401);
        }

        // Fetch all cart items for the user
        $cartItems = CartItem::with('artwork')
            ->where('user_id', $user->id)
            ->get();

        // Calculate total and items count
        $total = $cartItems->reduce(function ($carry, $item) {
            return $carry + ($item->price * $item->quantity);
        }, 0);

        $itemsCount = $cartItems->sum('quantity');

        // Fetch stored addresses for the user
        $addresses = $user->addresses()->get();

        return response()->json([
            'items_count' => $itemsCount,
            'total' => $total,
            'addresses' => $addresses
        ]);
    }
}
