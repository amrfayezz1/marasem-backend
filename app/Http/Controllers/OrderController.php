<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\Invoice;
use App\Models\CustomizedOrder;
use App\Models\PromoCode;
use App\Models\CustomizedOrderTranslation;
use App\Models\Language;
use App\Models\CreditTransaction;
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
     *             @OA\Property(property="amount", type="number", format="float", example=200.50, description="Final total amount of the order after discounts and credits"),
     *             @OA\Property(property="payment_method", type="string", enum={"cash", "paymob"}, example="cash", description="Payment method"),
     *             @OA\Property(property="promo_code", type="string", nullable=true, example="WELCOME10", description="Optional promo code for discount"),
     *             @OA\Property(property="use_marasem_credit", type="boolean", nullable=true, example=true, description="Optional flag to use available Marasem credit")
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
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="You must be logged in to place an order.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid total amount or promo code",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid total amount. Expected 190.50")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Payment intention creation failed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Failed to create payment intention.")
     *         )
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
            'payment_method' => 'required|in:cash,paymob',
            'promo_code' => 'nullable|string',
            'use_marasem_credit' => 'nullable|boolean', // Indicates if the user wants to use credit
        ]);

        // Fetch cart items and calculate total
        $cartItems = CartItem::with('artwork')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty.'], 400);
        }

        $originalTotal = $cartItems->reduce(function ($carry, $item) {
            return $carry + ($item->price * $item->quantity);
        }, 0);

        // Apply promo code
        $promoCode = null;
        $discount = 0;
        if ($request->promo_code) {
            $promoCode = PromoCode::where('code', $validated['promo_code'])->first();
            if ($promoCode && $promoCode->isValid()) {
                $discount = $promoCode->discount_type === 'fixed'
                    ? $promoCode->discount_value
                    : $originalTotal * ($promoCode->discount_value / 100);
                $promoCode->increment('usages');
            } else {
                return response()->json(['error' => 'Invalid promo code.'], 400);
            }
        }

        // Apply Marasem credit if requested
        $marasemCreditUsed = 0;
        if ($request->use_marasem_credit) {
            $marasemCreditUsed = $user->applyCredit($originalTotal - $discount);
        }

        // Final total
        $finalTotal = $originalTotal - $discount - $marasemCreditUsed;

        if ($validated['amount'] != $finalTotal) {
            $user->marasem_credit += $marasemCreditUsed;
            $user->save();
            return response()->json(['error' => "Invalid total amount. Expected $finalTotal"], 400);
        }

        // Create the order
        $order = Order::create([
            'user_id' => $user->id,
            'address_id' => $validated['address_id'],
            'total_amount' => $finalTotal,
            'original_total' => $originalTotal,
            'promo_code_id' => $promoCode?->id,
            'marasem_credit_used' => $marasemCreditUsed,
            'remaining_marasem_credit' => $user->marasem_credit,
            'status' => 'pending',
        ]);

        if ($marasemCreditUsed > 0) {
            CreditTransaction::create([
                'user_id' => $user->id,
                'type' => 'Buy',
                'amount' => -$marasemCreditUsed, // Deduction
                'reference' => "Order #{$order->id}",
                'description' => 'Used Marasem Credit for order payment.',
            ]);
        }

        // Add order items
        foreach ($cartItems as $cartItem) {
            $item = OrderItem::create([
                'order_id' => $order->id,
                'artwork_id' => $cartItem->artwork_id,
                'size' => $cartItem->size,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
            ]);
        }

        // Handle payment methods
        if ($validated['payment_method'] === 'cash') {
            Payment::create([
                'order_id' => $order->id,
                'method' => 'cash',
                'amount' => $finalTotal,
                'status' => 'pending',
            ]);

            // Generate Invoice
            $this->generateInvoice($order, $cartItems, $user);

            // Clear the cart
            CartItem::where('user_id', $user->id)->delete();

            return response()->json([
                'message' => 'Order placed successfully. Payment is cash on delivery.',
                'order' => $order,
            ]);
        }

        if ($validated['payment_method'] === 'paymob') {
            $paymobResponse = $this->createPaymobIntention($order, $cartItems, $finalTotal, $user, $originalTotal, $originalTotal - $finalTotal);
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

    protected function createPaymobIntention($order, $cartItems, $totalAmount, $user, $originalTotal, $discount)
    {
        $paymobUrl = "https://accept.paymob.com/v1/intention/";
        $paymobSecretKey = config('services.paymob.secret_key');
        $paymobPublicKey = config('services.paymob.public_key');
        $paymobIntegrationId = config('services.paymob.integration_id');
        $paymobApiKey = config('services.paymob.api_key');

        // Build request payload
        $totalDiscount = $discount;
        $remainingDiscount = $totalDiscount;

        $items = $cartItems->map(function ($item, $index) use ($totalDiscount, $originalTotal, &$remainingDiscount, $cartItems) {
            if ($totalDiscount > 0) {
                $proportionalDiscount = ($totalDiscount / $originalTotal) * $item->price;
            } else {
                $proportionalDiscount = 0;
            }

            $discountedPrice = max($item->price - $proportionalDiscount, 0);
            $discountedAmountCents = round($discountedPrice * 100);
            $remainingDiscount -= $proportionalDiscount;

            // Adjust the last item to ensure total matches
            if ($index === $cartItems->count() - 1 && $remainingDiscount > 0) {
                $discountedAmountCents -= round($remainingDiscount * 100);
            }

            return [
                'name' => $item->artwork->name,
                'amount' => $discountedAmountCents,
                'description' => $item->artwork->description ?? 'Artwork item',
                'quantity' => $item->quantity,
            ];
        })->toArray();

        // Calculate total from items to validate consistency
        $calculatedTotal = array_sum(array_column($items, 'amount')) / 100;
        if ($calculatedTotal != $totalAmount) {
            \Log::error($items);
            \Log::error("Calculated total ({$calculatedTotal}) does not match expected total ({$totalAmount}).");
            return null;
        }

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

        // Create the customized order
        $customizedOrder = CustomizedOrder::create([
            'user_id' => $user->id,
            'artwork_id' => $validated['artwork_id'],
            'desired_size' => $validated['desired_size'],
            'offering_price' => $validated['offering_price'],
            'address_id' => $validated['address_id'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending', // Default status
        ]);

        // Save the description in the translations table
        $preferredLanguageId = $user->preferred_language ?? Language::where('code', request()->cookie('locale', 'en'))->first()->id;
        if ($validated['description']) {
            CustomizedOrderTranslation::create([
                'customized_order_id' => $customizedOrder->id,
                'language_id' => $preferredLanguageId,
                'description' => $validated['description'],
            ]);
        }

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

        $preferredLanguageId = $user->preferred_language ?? Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        $customizedOrders = CustomizedOrder::with([
            'user.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'artwork.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'artwork.artist.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'address'
        ])
            ->whereHas('artwork', function ($query) use ($user) {
                $query->where('artist_id', $user->id); // Ensure the artist owns the artwork
            })
            ->where('status', 'pending') // Only show pending orders
            ->get();

        foreach ($customizedOrders as $order) {
            // Artwork translations
            $artworkTranslation = $order->artwork->translations->first();
            $order->artwork->name = $artworkTranslation->name ?? $order->artwork->name;
            $order->artwork->art_type = $artworkTranslation->art_type ?? $order->artwork->art_type;
            $order->artwork->description = $artworkTranslation->description ?? $order->artwork->description;

            // Artist translations
            $artistTranslation = $order->artwork->artist->translations->first();
            $order->artwork->artist->first_name = $artistTranslation->first_name ?? $order->artwork->artist->first_name;
            $order->artwork->artist->last_name = $artistTranslation->last_name ?? $order->artwork->artist->last_name;

            // User translations
            $userTranslation = $order->user->translations->first();
            $order->user->first_name = $userTranslation->first_name ?? $order->user->first_name;
            $order->user->last_name = $userTranslation->last_name ?? $order->user->last_name;

            // Customized Order translations
            $orderTranslation = $order->translations->first();
            $order->description = $orderTranslation->description ?? $order->description;
        }

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

        // Determine the preferred language for translations
        $preferredLanguageId = $user->preferred_language ?? Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        if ($request->has('order_id')) {
            $order = Order::with([
                'items.artwork.translations' => function ($query) use ($preferredLanguageId) {
                    $query->where('language_id', $preferredLanguageId);
                },
                'items.artwork.artist.translations' => function ($query) use ($preferredLanguageId) {
                    $query->where('language_id', $preferredLanguageId);
                },
                'payments',
                'address',
                'invoice'
            ])
                ->where('user_id', $user->id)
                ->where('id', $request->input('order_id'))
                ->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found.'], 404);
            }

            // Add translations to the artworks and artist fields
            foreach ($order->items as $item) {
                $artwork = $item->artwork;

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

            return response()->json([
                'order' => $order,
                'items_count' => $order->items->sum('quantity'),
            ]);
        }

        // Fetch all orders for the user
        $orders = Order::with([
            'items.artwork.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'items.artwork.artist.translations' => function ($query) use ($preferredLanguageId) {
                $query->where('language_id', $preferredLanguageId);
            },
            'payments',
            'address',
            'invoice'
        ])
            ->where('user_id', $user->id)
            ->get();

        // Transform the orders for the response
        $response = $orders->map(function ($order) use ($preferredLanguageId) {
            // Add translations to the artworks and artist fields
            foreach ($order->items as $item) {
                $artwork = $item->artwork;

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

        // Determine the preferred language for translations
        $preferredLanguageId = $user->preferred_language ?? Language::where('code', request()->cookie('locale', 'en'))->first()->id;

        // Fetch all orders that include the artist's artworks
        $orders = Order::with([
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
                $query->where('artist_id', $user->id); // Only include items where the artist owns the artwork
            })
            ->get();

        // Transform the orders for the response
        $response = $orders->map(function ($order) use ($user, $preferredLanguageId) {
            $artistItems = $order->items->filter(function ($item) use ($user) {
                return $item->artwork->artist_id === $user->id; // Filter items to only the artist's artworks
            });

            return [
                'order_id' => $order->id,
                'items' => $artistItems->map(function ($item) use ($preferredLanguageId) {
                    $artwork = $item->artwork;

                    // Artwork translations
                    $artworkTranslation = $artwork->translations->first();
                    $artwork->name = $artworkTranslation->name ?? $artwork->name;
                    $artwork->description = $artworkTranslation->description ?? $artwork->description;

                    // Artist translations
                    $artistTranslation = $artwork->artist->translations->first();
                    $artwork->artist->first_name = $artistTranslation->first_name ?? $artwork->artist->first_name;
                    $artwork->artist->last_name = $artistTranslation->last_name ?? $artwork->artist->last_name;

                    return [
                        'artwork_name' => $artwork->name,
                        'artwork_description' => $artwork->description,
                        'artist_name' => $artwork->artist->first_name . ' ' . $artwork->artist->last_name,
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

    /**
     * @OA\Post(
     *     path="/validate-promocode",
     *     summary="Validate a promo code",
     *     tags={"Orders"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"promo_code"},
     *             @OA\Property(
     *                 property="promo_code",
     *                 type="string",
     *                 example="WELCOME10",
     *                 description="The promo code to validate"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Promo code is valid",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="promo_code", type="string", example="WELCOME10", description="The validated promo code"),
     *             @OA\Property(property="discount_type", type="string", example="percentage", description="The type of discount (e.g., fixed or percentage)"),
     *             @OA\Property(property="discount_value", type="number", example=10, description="The value of the discount")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Promo code not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Invalid promo code.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Promo code is invalid or expired",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Promo code is not valid or has expired.")
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

    public function validatePromoCode(Request $request)
    {
        $validated = $request->validate([
            'promo_code' => 'required|string',
        ]);

        $promoCode = PromoCode::where('code', $validated['promo_code'])->first();

        if (!$promoCode) {
            return response()->json(['error' => 'Invalid promo code.'], 404);
        }

        if (!$promoCode->isValid()) {
            return response()->json(['error' => 'Promo code is not valid or has expired.'], 400);
        }

        return response()->json([
            'promo_code' => $promoCode->code,
            'discount_type' => $promoCode->discount_type,
            'discount_value' => $promoCode->discount_value,
        ]);
    }
}
