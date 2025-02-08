@extends('dashboard.layouts.app')

@section('content')
<div class="container dashboard">

<!-- Orders by Status -->
@if($ordersByStatus->isEmpty())
    <div class="alert alert-warning">{{ tt('Unable to load order fulfillment data. Please try again later.') }}</div>
@endif

    <h3>{{ tt('Order Fulfillment Insights') }}</h3>
    <hr>

    <!-- Date Range Filter -->
    <form method="GET" action="{{ route('dashboard.order-fulfillment') }}" class="mb-4">
        <div class="d-flex justify-content-between flex-wrap gap-4">
            <div class="row col-md-8">
                <div class="col-md-4">
                    <label>{{ tt('Start Date:') }}</label>
                    <input type="date" name="start_date"
                        value="{{ request('start_date', $startDate->format('Y-m-d')) }}" required class="form-control">
                </div>
                <div class="col-md-4">
                    <label>{{ tt('End Date:') }}</label>
                    <input type="date" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
                        required class="form-control">
                </div>
            </div>
            <div class="col-md-1 d-flex align-items-end justify-self-end">
                <button type="submit" class="btn btn-primary w-100">{{ tt('Filter') }}</button>
            </div>
        </div>
    </form>

    <!-- Orders by Status -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">{{ tt('Orders by Status') }}</h5>
                    <canvas id="ordersByStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Average Delivery Time -->
        <div class="col-md-6">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h5 class="card-title">{{ tt('Average Delivery Time') }}</h5>
                    <h2>{{ $averageDeliveryTime ? round($averageDeliveryTime, 2) . ' ' . tt('days') : tt('No data') }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Delayed Orders -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h5 class="card-title">{{ tt('Delayed Orders (Overdue > 7 Days)') }}</h5>
                    <h2>{{ $delayedOrders }}</h2>
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
    // Orders by Status Chart (Pie)
    var ordersByStatusChart = new Chart(document.getElementById('ordersByStatusChart'), {
        type: 'pie',
        data: {
            labels: @json($ordersByStatus->pluck('order_status')),
            datasets: [{
                data: @json($ordersByStatus->pluck('count')),
                backgroundColor: ['#6C63FF', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
            }]
        },
        options: {
            responsive: true
        }
    });
</script>
@endsection
