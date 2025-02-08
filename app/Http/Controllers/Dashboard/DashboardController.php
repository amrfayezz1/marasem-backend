<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Artwork;
use App\Models\User;
use App\Models\OrderItem;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\CustomReportExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;


class DashboardController extends Controller
{
    public function index()
    {
        // Fetching Key Metrics
        $total_sales = Order::where('order_status', 'completed')->sum('total_amount');
        $total_sessions = DB::table('sessions')->count();
        $total_product_views = Artwork::sum('views_count');
        $total_add_to_cart = CartItem::count();
        // $total_checkout = Order::where('status', 'checkout')->count();
        $total_purchases = Order::where('order_status', 'completed')->count();
        $popular_products = Artwork::orderByDesc('views_count')->take(5)->get();
        $top_sellers = User::where('is_artist', true)
            ->join('artworks', 'users.id', '=', 'artworks.artist_id')
            ->join('order_items', 'artworks.id', '=', 'order_items.artwork_id')
            ->select('users.first_name', 'users.last_name', DB::raw('SUM(order_items.price * order_items.quantity) as revenue'))
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderByDesc('revenue')
            ->take(10)
            ->get();

        return view('dashboard.insights.index', compact(
            'total_sales',
            'total_sessions',
            'total_product_views',
            'total_add_to_cart',
            // 'total_checkout',
            'total_purchases',
            'popular_products',
            'top_sellers'
        ));
    }

    public function sales(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate = $request->input('end_date', now());

        // Total revenue breakdown
        $revenueByCategory = DB::table('order_items')
            ->join('artworks', 'order_items.artwork_id', '=', 'artworks.id')
            ->join('artwork_tag', 'artworks.id', '=', 'artwork_tag.artwork_id')
            ->join('tags', 'artwork_tag.tag_id', '=', 'tags.id')
            ->join('categories', 'tags.category_id', '=', 'categories.id')
            ->select('categories.name as category', DB::raw('SUM(order_items.price * order_items.quantity) as revenue'))
            ->whereBetween('order_items.created_at', [$startDate, $endDate])
            ->groupBy('categories.name')
            ->get();

        $revenueByRegion = DB::table('orders')
            ->join('addresses', 'orders.address_id', '=', 'addresses.id')
            ->select('addresses.city', DB::raw('SUM(orders.total_amount) as revenue'))
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->groupBy('addresses.city')
            ->get();

        $revenueByPaymentMethod = DB::table('payments')
            ->select('payments.method', DB::raw('SUM(payments.amount) as revenue'))
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->groupBy('payments.method')
            ->get();

        // Sales trends
        $salesTrends = DB::table('orders')
            ->select(DB::raw("DATE(created_at) as date"), DB::raw("SUM(total_amount) as revenue"))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy('date')
            ->get();

        // Best-selling products
        $bestSellingProducts = DB::table('order_items')
            ->join('artworks', 'order_items.artwork_id', '=', 'artworks.id')
            ->select('artworks.name', DB::raw('SUM(order_items.quantity) as total_sold'), DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue'))
            ->whereBetween('order_items.created_at', [$startDate, $endDate])
            ->groupBy('artworks.name')
            ->orderByDesc('total_sold')
            ->take(10)
            ->get();

        // Average order value
        $totalRevenue = DB::table('orders')->whereBetween('created_at', [$startDate, $endDate])->sum('total_amount');
        $totalOrders = DB::table('orders')->whereBetween('created_at', [$startDate, $endDate])->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        return view('dashboard.insights.sales', compact(
            'revenueByCategory',
            'revenueByRegion',
            'revenueByPaymentMethod',
            'salesTrends',
            'bestSellingProducts',
            'averageOrderValue',
            'startDate',
            'endDate'
        ));
    }

    public function customer(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate = $request->input('end_date', now());

        // Number of active users (based on last login within 30 days)
        $activeUsers = User::where('last_active_at', '>=', now()->subDays(30))->count();

        // Customer acquisition sources (Assuming referral_source is stored)
        // $acquisitionSources = User::select('referral_source', DB::raw('COUNT(id) as count'))
        //     ->whereBetween('created_at', [$startDate, $endDate])
        //     ->groupBy('referral_source')
        //     ->get();

        // Browsing behavior - Popular categories
        $popularCategories = DB::table('order_items')
            ->join('artworks', 'order_items.artwork_id', '=', 'artworks.id')
            ->join('artwork_tag', 'artworks.id', '=', 'artwork_tag.artwork_id')
            ->join('tags', 'artwork_tag.tag_id', '=', 'tags.id')
            ->join('categories', 'tags.category_id', '=', 'categories.id')
            ->select('categories.name as category', DB::raw('SUM(artworks.views_count) as views'))
            ->whereBetween('order_items.created_at', [$startDate, $endDate])
            ->groupBy('categories.name')
            ->get();
        \Log::info($popularCategories);

        // Abandoned carts (users who added items but did not check out)
        $abandonedCarts = CartItem::select('user_id', DB::raw('COUNT(*) as cart_items'))
            ->groupBy('user_id')
            ->havingRaw('cart_items > 0')
            ->get()
            ->count();

        // Checkout drop-offs (users who started checkout but did not complete purchase)
        // $checkoutDropoffs = Order::where('status', 'checkout')->count();

        return view('dashboard.insights.customer_insights', compact(
            'activeUsers',
            // 'acquisitionSources',
            'popularCategories',
            'abandonedCarts',
            // 'checkoutDropoffs',
            'startDate',
            'endDate'
        ));
    }

    public function financial(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate = $request->input('end_date', now());

        // Breakdown of payments by method
        $paymentsByMethod = DB::table('payments')
            ->select('method', DB::raw('SUM(amount) as total_amount'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('method')
            ->get();

        // Total pending payments (Assuming status 'pending')
        $pendingPayments = DB::table('payments')
            ->where('status', 'pending')
            ->sum('amount');

        // Total refunds (Assuming status 'refunded')
        $totalRefunds = DB::table('payments')
            ->where('status', 'refunded')
            ->sum('amount');

        // Revenue trends segmented by payment method
        $revenueTrends = DB::table('payments')
            ->select(DB::raw("DATE(created_at) as date"), 'method', DB::raw("SUM(amount) as revenue"))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw("DATE(created_at)"), 'method')
            ->orderBy('date')
            ->get();

        return view('dashboard.insights.financial_insights', compact(
            'paymentsByMethod',
            'pendingPayments',
            'totalRefunds',
            'revenueTrends',
            'startDate',
            'endDate'
        ));
    }

    public function fulfillment(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate = $request->input('end_date', now());

        // Count orders by status
        $ordersByStatus = Order::select('status', DB::raw('COUNT(id) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();

        // Calculate average delivery time (assuming 'delivered_at' exists)
        $averageDeliveryTime = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw("AVG(TIMESTAMPDIFF(DAY, created_at, updated_at)) as avg_delivery_time"))
            ->value('avg_delivery_time');

        // Identify delayed orders (Assuming expected delivery is within 7 days)
        $delayedOrders = Order::where('status', 'shipped')
            ->whereRaw("DATEDIFF(NOW(), created_at) > 7")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return view('dashboard.insights.order_fulfillment', compact(
            'ordersByStatus',
            'averageDeliveryTime',
            'delayedOrders',
            'startDate',
            'endDate'
        ));
    }

    public function reports()
    {
        return view('dashboard.insights.custom_reports');
    }

    public function generateReport(Request $request)
    {
        // Validate input fields
        $request->validate([
            'metrics' => 'required|array',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf',
        ]);

        $metrics = $request->input('metrics');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $format = $request->input('format');

        // Fetch data based on selected metrics
        $reportData = [];

        if (in_array('sales', $metrics)) {
            $reportData['sales'] = DB::table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('id', 'user_id', 'total_amount', 'status', 'created_at')
                ->get();
        }

        if (in_array('inventory', $metrics)) {
            $reportData['inventory'] = DB::table('artworks')
                ->select('id', 'name', 'art_type', 'views_count', 'likes_count', 'created_at')
                ->get();
        }

        if (in_array('customer_behavior', $metrics)) {
            $reportData['customer_behavior'] = DB::table('users')
                ->select('id', 'first_name', 'last_name', 'email', 'created_at', 'last_active_at')
                ->get();
        }

        // Generate report in selected format
        if ($format === 'excel') {
            // return $this->export($request);
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('dashboard.insights.reports_pdf', compact('reportData'));
            return $pdf->download('custom_report.pdf');
        }

        return redirect()->back()->with('error', 'Unable to generate the report. Please try again.');
    }
}
