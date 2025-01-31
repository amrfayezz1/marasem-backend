@extends('dashboard.layouts.app')

@section('content')
<div class="container dashboard">
    <h3>Website Performance Overview</h3>
    <hr>

    <!-- KPI Metrics Row -->
    <div class="row mt-4">
        <!-- Total Sales Amount -->
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total Sales</h5>
                    <h2>${{ number_format($total_sales, 2) }}</h2>
                </div>
            </div>
        </div>

        <!-- Total Sessions -->
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total Active Sessions</h5>
                    <h2>{{ $total_sessions }}</h2>
                </div>
            </div>
        </div>

        <!-- Total Product Views -->
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total Product Views</h5>
                    <h2>{{ $total_product_views }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Top 5 Popular Products & Top 10 Sellers -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Top 5 Popular Products</h5>
                    <ul>
                        @foreach($popular_products as $product)
                            <li>{{ $product->name }} - {{ $product->sales }} Sales</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Top 10 Sellers</h5>
                    <ul>
                        @foreach($top_sellers as $seller)
                            <li>{{ $seller->name }} - ${{ number_format($seller->revenue, 2) }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Funnel Chart -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Sales Funnel</h5>
                    <canvas id="salesFunnelChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('dashboard').classList.add('active');
    document.querySelector('#dashboard .nav-link').classList.add('active');
</script>
<!-- Chart Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    var ctx = document.getElementById('salesFunnelChart').getContext('2d');
    var salesFunnelChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Sessions', 'Product Views', 'Add to Cart', 'Checkout', 'Purchases'],
            datasets: [{
                label: 'User Engagement',
                data: [{{ $total_sessions }}, {{ $total_product_views }}, {{ $total_add_to_cart }},
                    {{-- $total_checkout--}},
                {{ $total_purchases }}],
    backgroundColor: ['#6C63FF', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
            }]
        }
    });
</script>
@endsection