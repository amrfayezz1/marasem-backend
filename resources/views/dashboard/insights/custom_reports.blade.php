@extends('dashboard.layouts.app')

@section('content')
<div class="container dashboard">
    <h3>{{ tt('Generate Custom Reports') }}</h3>
    <hr>

    <!-- Report Generation Form -->
    <form method="POST" action="{{ route('dashboard.custom-reports.generate') }}">
        @csrf
        <div class="row">
            <!-- Select Metrics -->
            <div class="col-md-4">
                <label>{{ tt('Metrics to Include:') }}</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="metrics[]" value="sales" id="sales">
                    <label class="form-check-label" for="sales">{{ tt('Sales Data') }}</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="metrics[]" value="inventory" id="inventory">
                    <label class="form-check-label" for="inventory">{{ tt('Inventory Data') }}</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="metrics[]" value="customer_behavior"
                        id="customer_behavior">
                    <label class="form-check-label" for="customer_behavior">{{ tt('Customer Behavior') }}</label>
                </div>
            </div>

            <!-- Select Date Range -->
            <div class="col-md-4">
                <label>{{ tt('Start Date:') }}</label>
                <input type="date" name="start_date" class="form-control">
            </div>
            <div class="col-md-4">
                <label>{{ tt('End Date:') }}</label>
                <input type="date" name="end_date" class="form-control">
            </div>
        </div>

        <!-- Select Report Format -->
        <div class="row mt-3">
            <div class="col-md-6">
                <label>{{ tt('Export Format:') }}</label>
                <select name="format" class="form-control">
                    <option value="pdf">{{ tt('PDF') }}</option>
                    <!-- <option value="excel">Excel</option> -->
                </select>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="row mt-3">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary w-100">{{ tt('Generate Report') }}</button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('dashboard').classList.add('active');
    document.querySelector('#dashboard .nav-link').classList.add('active');
</script>
@endsection
