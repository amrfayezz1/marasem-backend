@extends('dashboard.layouts.app')

@section('content')
<div class="container dashboard">
    <h3>Payment and Financial Insights</h3>
    <hr>

    <!-- Date Range Filter -->
    <form method="GET" action="{{ route('dashboard.financial-insights') }}" class="mb-4">
        <div class="d-flex justify-content-between flex-wrap gap-4">
            <div class="row col-md-8">
                <div class="col-md-4">
                    <label>Start Date:</label>
                    <input type="date" name="start_date" value="{{ request('start_date', Date($startDate)) }}" required
                        class="form-control">
                </div>
                <div class="col-md-4">
                    <label>End Date:</label>
                    <input type="date" name="end_date" value="{{ request('end_date', Date($endDate)) }}" required
                        class="form-control">
                </div>
            </div>
            <div class="col-md-1 d-flex align-items-end justify-self-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <!-- Payment Breakdown & Pending Payments -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Payments by Method</h5>
                    <canvas id="paymentsByMethodChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Total Pending Payments & Refunds -->
        <div class="col-md-6">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Pending Payments</h5>
                    <h2>${{ number_format($pendingPayments, 2) }}</h2>
                </div>
            </div>
            <div class="card shadow-sm text-center mt-3">
                <div class="card-body">
                    <h5 class="card-title">Total Refunds</h5>
                    <h2>${{ number_format($totalRefunds, 2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Trends -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Revenue Trends by Payment Method</h5>
                    <canvas id="revenueTrendsChart"></canvas>
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
    // Payments by Method Chart (Pie)
    var paymentsByMethodChart = new Chart(document.getElementById('paymentsByMethodChart'), {
        type: 'pie',
        data: {
            labels: @json($paymentsByMethod->pluck('method')),
            datasets: [{
                data: @json($paymentsByMethod->pluck('total_amount')),
                backgroundColor: ['#6C63FF', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
            }]
        },
        options: {
            responsive: true
        }
    });

    // Revenue Trends by Payment Method Chart (Line)
    var revenueTrendsData = @json($revenueTrends->groupBy('method'));
    var revenueTrendsChart = new Chart(document.getElementById('revenueTrendsChart'), {
        type: 'line',
        data: {
            labels: @json($revenueTrends->pluck('date')->unique()),
            datasets: Object.keys(revenueTrendsData).map(method => ({
                label: method,
                data: revenueTrendsData[method].map(entry => entry.revenue),
                borderColor: '#' + Math.floor(Math.random() * 16777215).toString(16),
                fill: false
            }))
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