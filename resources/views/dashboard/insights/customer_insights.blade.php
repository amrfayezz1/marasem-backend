@extends('dashboard.layouts.app')

@section('content')
<div class="container dashboard">
    <h3>Customer Behavior Insights</h3>
    <hr>

    <!-- Date Range Filter -->
    <form method="GET" action="{{ route('dashboard.customer-insights') }}" class="mb-4">
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

    <!-- Active Users -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h5 class="card-title">Active Users (Last 30 Days)</h5>
                    <h2>{{ $activeUsers }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Acquisition Sources -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h5 class="card-title">Abandoned Carts</h5>
                    <h2>{{ $abandonedCarts }}</h2>
                </div>
            </div>
        </div>

        <!-- Popular Categories -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Popular Categories</h5>
                    <canvas id="popularCategoriesChart"></canvas>
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
    // Acquisition Source Chart (Pie)
    // var acquisitionChart = new Chart(document.getElementById('acquisitionChart'), {
    //     type: 'pie',
    //     data: {
    //         labels: json($acquisitionSources->pluck('referral_source')),
    //         datasets: [{
    //             data: json($acquisitionSources->pluck('count')),
    //             backgroundColor: ['#6C63FF', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
    //         }]
    //     },
    //     options: {
    //         responsive: true
    //     }
    // });

    // Popular Categories Chart (Bar)
    var popularCategoriesChart = new Chart(document.getElementById('popularCategoriesChart'), {
        type: 'bar',
        data: {
            labels: @json($popularCategories->pluck('category')),
            datasets: [{
                label: 'Views',
                data: @json($popularCategories->pluck('views')),
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
</script>
@endsection