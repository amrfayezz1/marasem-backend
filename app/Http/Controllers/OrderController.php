<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\Invoice;
use App\Models\CustomizedOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'You must be logged in to place an order.'], 401);
        }

        $validated = $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash', // Currently supporting only "cash"
        ]);

        // Validate the total amount against the cart
        $cartItems = CartItem::with('artwork')->where('user_id', $user->id)->get();
        $calculatedTotal = $cartItems->reduce(function ($carry, $item) {
            return $carry + ($item->price * $item->quantity);
        }, 0);

        if ($validated['amount'] != $calculatedTotal) {
            return response()->json(['error' => 'Invalid total amount.'], 400);
        }

        // Create the order
        $order = Order::create([
            'user_id' => $user->id,
            'address_id' => $validated['address_id'],
            'total_amount' => $calculatedTotal,
            'status' => 'pending',
        ]);

        // Add order items
        foreach ($cartItems as $cartItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'artwork_id' => $cartItem->artwork_id,
                'size' => $cartItem->size,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
            ]);
        }

        // Create a payment record
        Payment::create([
            'order_id' => $order->id,
            'method' => $validated['payment_method'],
            'amount' => $calculatedTotal,
            'status' => 'pending',
        ]);

        $invoiceNumber = 'MARASEM-INV-' . strtoupper(uniqid());
        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => $invoiceNumber,
            'amount' => $calculatedTotal,
            'status' => 'pending',
        ]);

        // Generate PDF
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'order' => $order,
            'cartItems' => $cartItems,
            'user' => $user,
        ]);
        $relativePath = "invoices/{$invoiceNumber}.pdf";
        \Storage::disk('public')->put($relativePath, $pdf->output());
        $fullPath = \Storage::disk('public')->url($relativePath);
        $invoice->update(['path' => $fullPath]);

        // Clear the user's cart
        CartItem::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => $order,
        ]);
    }

    public function placeCustomOrder(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'You must be logged in to submit a customized order.'], 401);
        }

        $validated = $request->validate([
            'artwork_id' => 'required|exists:artworks,id',
            'desired_size' => 'required|string',
            'offering_price' => 'required|numeric|min:0.01',
            'address_id' => 'required|exists:addresses,id',
            'description' => 'nullable|string',
        ]);

        $customizedOrder = CustomizedOrder::create([
            'user_id' => $user->id,
            'artwork_id' => $validated['artwork_id'],
            'desired_size' => $validated['desired_size'],
            'offering_price' => $validated['offering_price'],
            'address_id' => $validated['address_id'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending', // Default status
        ]);

        return response()->json([
            'message' => 'Customized order submitted successfully.',
            'customized_order' => $customizedOrder,
        ], 201);
    }

    public function showCustomizedForArtist()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'You must be logged in to view customized orders.'], 401);
        }

        $customizedOrders = CustomizedOrder::with('user', 'artwork', 'address')
            ->whereHas('artwork', function ($query) use ($user) {
                $query->where('artist_id', $user->id); // Ensure the artist owns the artwork
            })
            ->where('status', 'pending') // Only show pending orders
            ->get();

        return response()->json([
            'customized_orders' => $customizedOrders,
        ]);
    }

    public function viewOrders(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'You must be logged in to view your orders.'], 401);
        }

        if ($request->has('order_id')) {
            $order = Order::with(['items.artwork', 'payments', 'address', 'invoice'])
                ->where('user_id', $user->id)
                ->where('id', $request->input('order_id'))
                ->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found.'], 404);
            }

            return response()->json([
                'order' => $order,
                'items_count' => $order->items->sum('quantity'),
            ]);
        }

        // Fetch all orders for the user
        $orders = Order::with(['items.artwork', 'payments', 'address', 'invoice'])
            ->where('user_id', $user->id)
            ->get();

        // Transform the orders for the response
        $response = $orders->map(function ($order) {
            return [
                'order' => $order,
                'items_count' => $order->items->sum('quantity'),
            ];
        });

        return response()->json($response);
    }

    public function viewOrdersForArtist()
    {
        $user = Auth::user();

        if (!$user || !$user->is_artist) {
            return response()->json(['error' => 'Only artists can view these orders.'], 403);
        }

        // Fetch all orders that include the artist's artworks
        $orders = Order::with(['items.artwork', 'address', 'payments', 'invoice'])
            ->whereHas('items.artwork', function ($query) use ($user) {
                $query->where('artist_id', $user->id); // Only include items where the artist owns the artwork
            })
            ->get();

        // Transform the orders for the response
        $response = $orders->map(function ($order) use ($user) {
            $artistItems = $order->items->filter(function ($item) use ($user) {
                return $item->artwork->artist_id === $user->id; // Filter items to only the artist's artworks
            });

            return [
                'order_id' => $order->id,
                'items' => $artistItems->map(function ($item) {
                    return [
                        'artwork_name' => $item->artwork->name,
                        'size' => $item->size,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->quantity * $item->price,
                    ];
                }),
                'items_count' => $artistItems->sum('quantity'),
                'total' => $artistItems->reduce(function ($carry, $item) {
                    return $carry + ($item->price * $item->quantity);
                }, 0),
                'invoice_path' => $order->invoice->path ?? null,
                'selected_address' => $order->address,
                'payment_method' => $order->payments->first()->method ?? 'Unknown',
            ];
        });

        return response()->json($response);
    }
}
