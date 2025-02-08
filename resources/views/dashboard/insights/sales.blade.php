@extends('dashboard.layouts.app')

@section('content')
<div class="container dashboard">
    <h3>{{ tt('Sales Insights') }}</h3>
    <hr>

    <!-- Date Range Filter -->
    <form method="GET" action="{{ route('dashboard.sales') }}" class="mb-4">
        <div class="d-flex justify-content-between flex-wrap gap-4">
            <div class="row col-md-8">
                <div class="col-md-4">
                    <label>{{ tt('Start Date:') }}</label>
                    <input type="date" name="start_date"
                        value="{{ request('start_date', $startDate->format('Y-m-d')) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>{{ tt('End Date:') }}</label>
                    <input type="date" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
                        class="form-control">
                </div>
            </div>
            <div class="col-md-1 d-flex align-items-end justify-self-end">
                <button type="submit" class="btn btn-primary w-100">{{ tt('Filter') }}</button>
            </div>
        </div>
    </form>

    <!-- Total Revenue Breakdown (By Category & Payment Method) -->
    <div class="row mt-4">
        <!-- Revenue by Category -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">{{ tt('Revenue by Category') }}</h5>
                    <canvas id="categoryRevenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Revenue by Payment Method -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">{{ tt('Revenue by Payment Method') }}</h5>
                    <canvas id="paymentMethodChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Trends -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">{{ tt('Sales Trends') }}</h5>
                    <canvas id="salesTrendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Best-Selling Products -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">{{ tt('Top 10 Best-Selling Products') }}</h5>
                    <ul>
                        @foreach($bestSellingProducts as $product)
                            <li>{{ $product->name }} - {{ $product->total_sold }} {{ tt('Sold') }}
                                (${{ number_format($product->total_revenue, 2) }})</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Average Order Value -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">{{ tt('Average Order Value') }}</h5>
                    <h2>${{ number_format($averageOrderValue, 2) }}</h2>
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
    // Revenue by Category Chart (Pie)
    var categoryRevenueChart = new Chart(document.getElementById('categoryRevenueChart'), {
        type: 'pie',
        data: {
            labels: @json($revenueByCategory->pluck('category')),
            datasets: [{
                data: @json($revenueByCategory->pluck('revenue')),
                backgroundColor: ['#6C63FF', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
            }]
        },
        options: {
            responsive: true
        }
    });

    // Revenue by Payment Method Chart (Bar)
    var paymentMethodChart = new Chart(document.getElementById('paymentMethodChart'), {
        type: 'bar',
        data: {
            labels: @json($revenueByPaymentMethod->pluck('method')),
            datasets: [{
                label: '{{ tt('Revenue') }}',
                data: @json($revenueByPaymentMethod->pluck('revenue')),
                backgroundColor: '#36A2EB'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Sales Trends Chart (Line)
    var salesTrendsChart = new Chart(document.getElementById('salesTrendsChart'), {
        type: 'line',
        data: {
            labels: @json($salesTrends->pluck('date')),
            datasets: [{
                label: '{{ tt('Revenue') }}',
                data: @json($salesTrends->pluck('revenue')),
                borderColor: '#6C63FF',
                fill: false
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
@endsection