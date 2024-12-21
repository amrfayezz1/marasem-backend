<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Invoice;

class PaymobController extends Controller
{
    /**
     * Handle the Transaction Processed Callback.
     */
    public function processedCallback(Request $request)
    {
        $data = $request->all();

        // Validate HMAC
        if (!$this->validateHmac($data, config('services.paymob.hmac'))) {
            Log::warning('Invalid HMAC in processed callback.');
            return response()->json(['error' => 'Invalid HMAC.'], 403);
        }

        $transaction = $data['type'] == "TRANSACTION" ?? null;
        if (!$transaction) {
            Log::error('Transaction data missing in processed callback.');
            return response()->json(['error' => 'Transaction data missing.'], 400);
        }

        $merchantOrderId = $data['obj']['order']['merchant_order_id'] ?? null; // Matches `order-<id>` format
        $orderId = explode('-', $merchantOrderId)[1] ?? null;

        // Validate the order
        $order = Order::find($orderId);
        if (!$order) {
            Log::error("Order not found for merchant_order_id: $merchantOrderId");
            return response()->json(['error' => 'Order not found.'], 404);
        }

        // Update order and payment records
        if ($data['obj']['success']) {
            // Update order status
            $order->update(['status' => 'paid']);

            // Update payment record
            Payment::where('order_id', $order->id)
                ->update([
                    'status' => 'paid',
                    'transaction_id' => $data['obj']['id'],
                    'amount' => $data['obj']['amount_cents'] / 100, // Convert cents to EGP
                    'extra_data' => json_encode($data), // Store full response for future reference
                ]);

            // Generate Invoice
            $this->generateInvoice($order);

            // Clear user's cart
            CartItem::where('user_id', $order->user_id)->delete();

            Log::info("Order #$orderId marked as paid. Invoice generated, and cart cleared.");
        } else {
            // Mark order as failed
            $order->update(['status' => 'failed']);

            // Update payment record
            Payment::where('order_id', $order->id)
                ->update(['status' => 'failed']);

            Log::warning("Payment for Order #$orderId failed through processed callback.");
        }

        return response()->json(['status' => 'processed']);
    }

    /**
     * Handle the Transaction Response Callback.
     */
    public function responseCallback(Request $request)
    {
        $data = $request->query();

        if (!$this->validateHmac($data, config('services.paymob.hmac'))) {
            Log::warning('Invalid HMAC in response callback.');
            return redirect()->route('payment.error')->with('error', 'Invalid HMAC. Please try again.');
        }

        // Extract merchant order ID
        $merchantOrderId = $data['merchant_order_id'] ?? null; // Matches `order-<id>` format
        $orderId = explode('-', $merchantOrderId)[1] ?? null;

        // Validate the order
        $order = Order::find($orderId);
        if (!$order) {
            Log::error("Order not found for merchant_order_id: $merchantOrderId");
            return redirect()->route('payment.error')->with('error', 'Order not found.');
        }

        // Check if the payment was successful
        if ($data['success'] === "true") {
            // Redirect to success page
            return redirect()->route('payment.success')->with('success', 'Payment completed successfully!');
        } else {
            // Redirect to failure page
            return redirect()->route('payment.error')->with('error', 'Payment failed. Please try again.');
        }
    }

    /**
     * Generate an Invoice for the Order.
     */
    protected function generateInvoice($order)
    {
        $invoiceNumber = 'MARASEM-INV-' . strtoupper(uniqid());
        $cartItems = OrderItem::where('order_id', $order->id)->get();
        $user = $order->user; // Assuming there's a relationship between orders and users

        // Create invoice record
        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => $invoiceNumber,
            'amount' => $order->total_amount,
            'status' => 'paid',
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

        // Update invoice path
        $invoice->update(['path' => $fullPath]);

        Log::info("Invoice generated for Order #{$order->id}: {$fullPath}");
    }

    /**
     * Validate the HMAC for security.
     */
    protected function validateHmac(array $data, string $secret): bool
    {
        $hmac = $data['hmac'] ?? null;
        unset($data['hmac']);

        $keys = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order.id',
            'owner',
            'pending',
            'source_data.pan',
            'source_data.sub_type',
            'source_data.type',
            'success'
        ];

        $concatenatedString = '';
        foreach ($keys as $key) {
            $nestedKeys = explode('.', $key);
            $value = $data['obj'];
            foreach ($nestedKeys as $nestedKey) {
                $value = $value[$nestedKey] ?? '';
            }
            // Convert boolean to string "true" or "false"
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $concatenatedString .= $value;
        }
        $calculatedHmac = hash_hmac('sha512', $concatenatedString, $secret);
        return $hmac === $calculatedHmac;
    }
}
