<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Tag;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Artwork;
use App\Models\Category;
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
        $userPreferredLanguage = auth()->user()->preferred_language;

        // Fetch Key Metrics
        $total_sales = Order::where('order_status', 'completed')->sum('total_amount');
        $total_sessions = DB::table('sessions')->count();
        $total_product_views = Artwork::sum('views_count');
        $total_add_to_cart = CartItem::count();
        $total_purchases = Order::where('order_status', 'completed')->count();
        \Log::info('total_purchases Sales: ' . $total_purchases);

        // Popular products: override name with translation if available.
        $popular_products = Artwork::where('reviewed', '=', 1)->orderByDesc('views_count')->take(5)->get();
        foreach ($popular_products as $artwork) {
            $translation = $artwork->translations->where('language_id', $userPreferredLanguage)->first();
            if ($translation) {
                $artwork->name = $translation->name;
                // Optionally override art_type and description if needed:
                $artwork->art_type = $translation->art_type;
                $artwork->description = $translation->description;
            }
        }

        // Top sellers: retrieve aggregated sellers and then override their names.
        $top_sellers = User::with('artistDetails')->where('is_artist', true)
            ->whereHas('artistDetails', function ($q) {
                $q->where('status', 'approved');
            })
            ->join('artworks', 'users.id', '=', 'artworks.artist_id')
            ->join('order_items', 'artworks.id', '=', 'order_items.artwork_id')
            ->select('users.id', 'users.first_name', 'users.last_name', DB::raw('SUM(order_items.price * order_items.quantity) as revenue'))
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderByDesc('revenue')
            ->take(5)
            ->get();
        foreach ($top_sellers as $seller) {
            // Re-fetch the full seller model to access translations.
            $fullSeller = User::with('translations')->find($seller->id);
            if ($fullSeller) {
                $translation = $fullSeller->translations->where('language_id', $userPreferredLanguage)->first();
                if ($translation) {
                    $seller->first_name = $translation->first_name;
                    $seller->last_name = $translation->last_name;
                }
            }
        }

        return view('dashboard.insights.index', compact(
            'total_sales',
            'total_sessions',
            'total_product_views',
            'total_add_to_cart',
            'total_purchases',
            'popular_products',
            'top_sellers'
        ));
    }

    public function sales(Request $request)
    {
        $userPreferredLanguage = auth()->user()->preferred_language;
        $startDate = \Carbon\Carbon::parse($request->input('start_date', now()->subDays(30)));
        $endDate = \Carbon\Carbon::parse($request->input('end_date', now()));

        // Revenue by Category using OrderItems from reviewed artworks
        $revenueByCategory = OrderItem::with('artwork.tags')
            ->whereHas('artwork', function ($query) {
                $query->where('reviewed', '=', 1);
            })
            ->select('artwork_id', DB::raw('SUM(price * quantity) as revenue'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('artwork_id')
            ->get();

        foreach ($revenueByCategory as $record) {
            // Update each tag's name using its translation for the user's preferred language
            foreach ($record->artwork->tags as $tag) {
                $translation = $tag->translations->where('language_id', $userPreferredLanguage)->first();
                if ($translation) {
                    $tag->name = $translation->name;
                }
            }
            // Use the first tag's name as the "tag" attribute; if no tag exists, default to "N/A"
            $record->tag = $record->artwork->tags->first() ? $record->artwork->tags->first()->name : 'N/A';
        }

        // Aggregate revenue by distinct tag name
        $aggregated = $revenueByCategory->groupBy('tag')->map(function ($group) {
            return $group->sum('revenue');
        });
        $labels = $aggregated->keys();
        $data = $aggregated->values();

        // Revenue by Region (using city names)
        $revenueByRegion = DB::table('orders')
            ->where('order_status', '!=', 'deleted')
            ->join('addresses', 'orders.address_id', '=', 'addresses.id')
            ->select('addresses.city', DB::raw('SUM(orders.total_amount) as revenue'))
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->groupBy('addresses.city')
            ->get();

        // Revenue by Payment Method (translate method names using helper tt())
        $revenueByPaymentMethod = DB::table('payments')
            ->select('payments.method', DB::raw('SUM(payments.amount) as revenue'))
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->groupBy('payments.method')
            ->get();
        foreach ($revenueByPaymentMethod as $record) {
            $record->method = tt($record->method);
        }

        // Sales trends: daily revenue
        $salesTrends = DB::table('orders')
            ->where('order_status', '!=', 'deleted')
            ->select(DB::raw("DATE(created_at) as date"), DB::raw("SUM(total_amount) as revenue"))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy('date')
            ->get();

        // Best-selling products: override artwork name with translation if available.
        $bestSellingProducts = DB::table('order_items')
            ->join('artworks', 'order_items.artwork_id', '=', 'artworks.id')
            ->select('artworks.id', 'artworks.name', DB::raw('SUM(order_items.quantity) as total_sold'), DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue'))
            ->whereBetween('order_items.created_at', [$startDate, $endDate])
            ->groupBy('artworks.id', 'artworks.name')
            ->orderByDesc('total_sold')
            ->take(10)
            ->get();
        foreach ($bestSellingProducts as $product) {
            $artwork = \App\Models\Artwork::with('translations')->find($product->id);
            if ($artwork) {
                $translation = $artwork->translations->where('language_id', $userPreferredLanguage)->first();
                if ($translation) {
                    $product->name = $translation->name;
                }
            }
        }

        $totalRevenue = DB::table('orders')
            ->where('order_status', '!=', 'deleted')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');
        $totalOrders = DB::table('orders')
            ->where('order_status', '!=', 'deleted')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        return view('dashboard.insights.sales', compact(
            'labels',
            'data',
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
        $userPreferredLanguage = auth()->user()->preferred_language;
        $startDate = \Carbon\Carbon::parse($request->input('start_date', now()->subDays(30)));
        $endDate = \Carbon\Carbon::parse($request->input('end_date', now()));

        // Active users based on last activity in the past 30 days.
        $activeUsers = User::where('last_active_at', '>=', now()->subDays(30))->count();

        // Popular Categories: Calculate the sum of artworks.views_count grouped by category.
        // Note: This query assumes that an artwork can have one or more tags,
        // each tag belongs to a category, and the Category model also has a 'translations' relationship.
        $popularCategories = DB::table('artworks')
            ->join('artwork_tag', 'artworks.id', '=', 'artwork_tag.artwork_id')
            ->join('tags', 'artwork_tag.tag_id', '=', 'tags.id')
            ->join('categories', 'tags.category_id', '=', 'categories.id')
            ->select('categories.name as category', DB::raw('SUM(artworks.views_count) as views'))
            ->whereBetween('artworks.created_at', [$startDate, $endDate])
            ->groupBy('categories.name')
            ->get();

        // Update each record with the translated category name (if available)
        foreach ($popularCategories as $record) {
            // Find the Category model by the base name.
            // (If your Category model has a unique id, it is better to join on that. Here we use name as provided.)
            $category = Category::where('name', $record->category)->first();
            if ($category) {
                $translation = $category->translations->where('language_id', $userPreferredLanguage)->first();
                if ($translation) {
                    $record->category = $translation->name;
                }
            }
        }

        // Count abandoned carts (users with one or more items in their cart)
        $abandonedCarts = CartItem::select('user_id', DB::raw('COUNT(*) as cart_items'))
            ->groupBy('user_id')
            ->havingRaw('cart_items > 0')
            ->get()
            ->count();

        return view('dashboard.insights.customer_insights', compact(
            'activeUsers',
            'popularCategories',
            'abandonedCarts',
            'startDate',
            'endDate'
        ));
    }

    public function financial(Request $request)
    {
        $userPreferredLanguage = auth()->user()->preferred_language;
        $startDate = \Carbon\Carbon::parse($request->input('start_date', now()->subDays(30)));
        $endDate = \Carbon\Carbon::parse($request->input('end_date', now()));

        // Revenue by Payment Method (Pie Chart Data)
        $paymentsByMethod = DB::table('payments')
            ->select('payments.method', DB::raw('SUM(amount) as total_amount'))
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->groupBy('payments.method')
            ->get();
        foreach ($paymentsByMethod as $record) {
            $record->method = tt($record->method);
        }

        // Total pending payments (status 'pending')
        $pendingPayments = DB::table('payments')
            ->where('status', 'pending')
            ->sum('amount');

        // Total refunds (status 'refunded')
        $totalRefunds = DB::table('payments')
            ->where('status', 'refunded')
            ->sum('amount');

        // Revenue Trends by Payment Method (Line Chart Data)
        $revenueTrends = DB::table('payments')
            ->select(DB::raw("DATE(created_at) as date"), 'method', DB::raw("SUM(amount) as revenue"))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw("DATE(created_at)"), 'method')
            ->orderBy('date')
            ->get();
        foreach ($revenueTrends as $record) {
            $record->method = tt($record->method);
        }

        // Extract all unique dates (for the x-axis), sorted
        $trendDates = $revenueTrends->pluck('date')->unique()->sort()->values();

        return view('dashboard.insights.financial_insights', compact(
            'paymentsByMethod',
            'pendingPayments',
            'totalRefunds',
            'revenueTrends',
            'trendDates',
            'startDate',
            'endDate'
        ));
    }

    public function fulfillment(Request $request)
    {
        try {
            $userPreferredLanguage = auth()->user()->preferred_language;
            $startDate = $request->input('start_date', now()->subDays(30));
            $endDate = $request->input('end_date', now());

            // Orders by Status
            $ordersByStatus = Order::select('order_status', DB::raw('COUNT(id) as count'))
                ->where('order_status', '!=', 'deleted')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('order_status')
                ->get();
            foreach ($ordersByStatus as $record) {
                // Translate the order status using your tt() helper
                $record->order_status = tt($record->order_status);
            }

            // Average Delivery Time for delivered orders
            // Calculate the average difference (in days) between created_at and updated_at.
            $averageDeliveryTime = Order::where('order_status', '!=', 'deleted')
                ->where('order_status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(DB::raw("AVG(TIMESTAMPDIFF(DAY, created_at, updated_at)) as avg_delivery_time"))
                ->value('avg_delivery_time');

            // Delayed Orders:
            // Count orders (not deleted) where the delivery time is more than 7 days.
            // For delivered orders, use updated_at; for pending/shipped orders, use NOW().
            $delayedOrders = Order::where('order_status', '!=', 'deleted')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereRaw("DATEDIFF(IF(order_status = 'completed', updated_at, NOW()), created_at) > 7")
                ->count();

            return view('dashboard.insights.order_fulfillment', compact(
                'ordersByStatus',
                'averageDeliveryTime',
                'delayedOrders',
                'startDate',
                'endDate'
            ));
        } catch (\Exception $e) {
            \Log::error("Error in fulfillment(): " . $e->getMessage());
            return redirect()->back()->with('error', tt('Unable to load order fulfillment data. Please try again later.'));
        }
    }

    public function reports()
    {
        return view('dashboard.insights.custom_reports');
    }

    public function generateReport(Request $request)
    {
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

        $reportData = [];

        if (in_array('sales', $metrics)) {
            $reportData['sales'] = DB::table('orders')
                ->where('order_status', '!=', 'deleted')
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

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('dashboard.insights.reports_pdf', compact('reportData'));
            return $pdf->download('custom_report.pdf');
        }

        return redirect()->back()->with('error', 'Unable to generate the report. Please try again.');
    }
}
