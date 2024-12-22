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
use Http;

class OrderController extends Controller
{
    /**
 * @OA\Post(
 *     path="/order",
 *     summary="Place a new order",
 *     tags={"Orders"},
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"address_id", "amount", "payment_method"},
 *             @OA\Property(property="address_id", type="integer", example=1, description="ID of the address for delivery"),
 *             @OA\Property(property="amount", type="number", format="float", example=200.50, description="Total amount of the order"),
 *             @OA\Property(property="payment_method", type="string", enum={"cash", "paymob"}, example="cash", description="Payment method")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order placed successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Order placed successfully. Payment is cash on delivery."),
 *             @OA\Property(property="order", ref="#/components/schemas/Order")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized access"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid total amount"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Payment intention creation failed"
 *     )
 * )
 */

    public function placeOrder(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'You must be logged in to place an order.'], 401);
        }

        $validated = $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,paymob', // Support "cash" and "paymob"
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

        // Handle payment method
        if ($validated['payment_method'] === 'cash') {
            // Create a payment record for cash
            Payment::create([
                'order_id' => $order->id,
                'method' => 'cash',
                'amount' => $calculatedTotal,
                'status' => 'pending',
            ]);

            // Generate Invoice
            $this->generateInvoice($order, $cartItems, $user);

            // Clear the user's cart
            CartItem::where('user_id', $user->id)->delete();

            return response()->json([
                'message' => 'Order placed successfully. Payment is cash on delivery.',
                'order' => $order,
            ]);
        }

        // Handle Paymob payment method
        if ($validated['payment_method'] === 'paymob') {
            $paymobResponse = $this->createPaymobIntention($order, $cartItems, $calculatedTotal, $user);
            if (!$paymobResponse) {
                return response()->json(['error' => 'Failed to create payment intention.'], 500);
            }

            return response()->json([
                'message' => 'Order placed successfully. Redirect to Paymob for payment.',
                'redirect_url' => $paymobResponse['redirect_url'],
                'order' => $order,
            ]);
        }
    }

    protected function createPaymobIntention($order, $cartItems, $totalAmount, $user)
    {
        $paymobUrl = "https://accept.paymob.com/v1/intention/";
        $paymobSecretKey = config('services.paymob.secret_key');
        $paymobPublicKey = config('services.paymob.public_key');
        $paymobIntegrationId = config('services.paymob.integration_id');
        $paymobApiKey = config('services.paymob.api_key');

        // Build request payload
        $items = $cartItems->map(function ($item) {
            return [
                'name' => $item->artwork->name,
                'amount' => $item->price * 100, // Convert to cents
                'description' => $item->artwork->description ?? 'Artwork item',
                'quantity' => $item->quantity,
            ];
        })->toArray();

        $payload = [
            'amount' => $totalAmount * 100, // Convert to cents
            'currency' => 'EGP',
            'items' => $items,
            'payment_methods' => [(int) $paymobIntegrationId],
            'billing_data' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone_number' => $user->phone,
                'email' => $user->email,
            ],
            'special_reference' => "order-{$order->id}",
        ];

        // Make API request
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$paymobSecretKey}",
            'Content-Type' => 'application/json',
        ])->post($paymobUrl, $payload);

        if ($response->failed()) {
            \Log::error($response->body());
            return null;
        }

        $responseData = $response->json();

        // Store Paymob data in the database
        Payment::create([
            'order_id' => $order->id,
            'method' => 'paymob',
            'amount' => $totalAmount,
            'status' => 'pending',
            'extra_data' => json_encode($responseData), // Store all Paymob response data for future use
        ]);

        // Construct the redirect URL
        $redirectUrl = "https://accept.paymob.com/unifiedcheckout/?publicKey={$paymobPublicKey}&clientSecret={$responseData['client_secret']}";

        return [
            'redirect_url' => $redirectUrl,
            'response_data' => $responseData,
        ];
    }

    protected function generateInvoice($order, $cartItems, $user)
    {
        $invoiceNumber = 'MARASEM-INV-' . strtoupper(uniqid());
        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => $invoiceNumber,
            'amount' => $order->total_amount,
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

        return $invoice;
    }

    /**
 * @OA\Post(
 *     path="/custom-order",
 *     summary="Place a customized order",
 *     tags={"Orders"},
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"artwork_id", "desired_size", "offering_price", "address_id"},
 *             @OA\Property(property="artwork_id", type="integer", example=1, description="ID of the artwork for customization"),
 *             @OA\Property(property="desired_size", type="string", example="36x48", description="Desired size of the artwork"),
 *             @OA\Property(property="offering_price", type="number", format="float", example=300.00, description="Offered price for customization"),
 *             @OA\Property(property="address_id", type="integer", example=1, description="ID of the address for delivery"),
 *             @OA\Property(property="description", type="string", nullable=true, example="I want this artwork in a larger size.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Customized order placed successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Customized order submitted successfully."),
 *             @OA\Property(property="customized_order", ref="#/components/schemas/CustomizedOrder")
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

    /**
 * @OA\Get(
 *     path="/artist/customized-orders",
 *     summary="View customized orders for the artist",
 *     tags={"Artist Orders"},
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Customized orders fetched successfully",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/CustomizedOrder")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized access"
 *     )
 * )
 */

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

    /**
 * @OA\Get(
 *     path="/orders",
 *     summary="View user orders",
 *     tags={"Orders"},
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="order_id",
 *         in="query",
 *         required=false,
 *         description="Specific order ID to view details",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Orders fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="orders",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/Order")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized access"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Order not found"
 *     )
 * )
 */

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

    /**
 * @OA\Get(
 *     path="/artist/orders",
 *     summary="View orders containing the artist's artworks",
 *     tags={"Artist Orders"},
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Orders for the artist fetched successfully",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="order_id", type="integer", example=1),
 *                 @OA\Property(
 *                     property="items",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="artwork_name", type="string", example="Sunset Painting"),
 *                         @OA\Property(property="size", type="string", example="24x36"),
 *                         @OA\Property(property="quantity", type="integer", example=2),
 *                         @OA\Property(property="price", type="number", example=200.50),
 *                         @OA\Property(property="total", type="number", example=401.00)
 *                     )
 *                 ),
 *                 @OA\Property(property="items_count", type="integer", example=3),
 *                 @OA\Property(property="total", type="number", example=1200.50),
 *                 @OA\Property(property="invoice_path", type="string", example="/invoices/12345.pdf"),
 *                 @OA\Property(property="selected_address", ref="#/components/schemas/Address"),
 *                 @OA\Property(property="payment_method", type="string", example="cash")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized access"
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Access restricted to artists only"
 *     )
 * )
 */

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
