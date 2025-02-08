<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\Address;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'address'])->where('order_status', '!=', 'deleted');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('id', $search)
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('order_status', $request->status);
        }

        if ($request->has('date_range') && !empty($request->date_range)) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) == 2) {
                $query->whereBetween('created_at', [$dates[0], $dates[1]]);
            }
        }

        $orders = $query->paginate(10);

        return view('dashboard.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with([
            'user',
            'address',
            'items.artwork' // Assuming `items` relation connects `order_items` to `artworks`
        ])->findOrFail($id);

        return response()->json(['order' => $order]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'order_status' => 'required|in:pending,completed,cancelled',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'address' => 'required|string|max:255',
        ], [
            'order_status.required' => 'Order status is required.',
            'customer_name.required' => 'Customer name is required.',
            'customer_phone.required' => 'Customer phone is required.',
            'address.required' => 'Address is required.',
        ]);

        $order->update($request->only([
            'order_status',
            // 'user_id',
            // 'address_id'
        ]));

        return redirect()->back()->with('success', 'Order updated successfully.');
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        // Example: Check if order is locked (you need a field or method to determine that)
        // if ($order->isLocked()) {
        //     return redirect()->back()->with('error', 'Unable to delete order. Please check the order status and try again.');
        // }
        $order->update(['order_status' => 'deleted']);

        return redirect()->back()->with('success', 'Order deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = json_decode($request->input('ids', []));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No orders selected for deletion.');
        }

        Order::whereIn('id', $ids)->update(['order_status' => 'deleted']);

        return redirect()->back()->with('success', 'Selected orders deleted successfully.');
    }

    public function bulkUpdateStatus(Request $request)
    {
        $ids = json_decode($request->input('ids', []));
        $newStatus = $request->input('status');

        if (empty($ids) || !in_array($newStatus, ['pending', 'completed', 'cancelled'])) {
            return redirect()->back()->with('error', 'Invalid selection.');
        }

        Order::whereIn('id', $ids)->update(['order_status' => $newStatus]);

        return redirect()->back()->with('success', 'Selected orders updated successfully.');
    }
}