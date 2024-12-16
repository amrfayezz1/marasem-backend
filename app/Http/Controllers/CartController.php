<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
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

        return response()->json([
            'cart_items' => $cartItems,
            'items_count' => count($cartItems),
            'total' => $total
        ]);
    }

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
