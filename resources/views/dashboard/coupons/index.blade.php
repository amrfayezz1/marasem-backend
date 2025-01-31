@extends('dashboard.layouts.app')

<link href="{{ asset('styles/dashboard/categories.css') }}" rel="stylesheet">
<link href="{{ asset('styles/dashboard/blogs.css') }}" rel="stylesheet">
<link href="{{ asset('styles/dashboard/coupons.css') }}" rel="stylesheet">
@section('content')
<div class="container home">
    <h3>
        Coupons
    </h3>
    <hr>
    <div class="content">
        <div class="d-flex justify-content-between seperate">
            <form action="{{ route('coupons.search') }}" method="GET" class="d-flex m-0">
                <!-- reset -->
                @if (isset($_GET['query']) || isset($_GET['filter']))
                    <a href="{{ route('coupons.index') }}" class="btn btn-secondary me-2">Reset</a>
                @endif
                <div class="input-group">
                    <input type="text" name="query" class="form-control" aria-label="Search..." placeholder="Search..."
                        required style="flex: 3;" value="{{ isset($_GET['query']) ? $_GET['query'] : '' }}">
                    <select name="filter" class="form-select me-2" required>
                        <option value="id" {{ isset($_GET['filter']) && $_GET['filter'] == 'id' ? 'selected' : '' }}>ID
                        </option>
                        <option value="code" {{ isset($_GET['filter']) && $_GET['filter'] == 'code' ? 'selected' : '' }}>
                            Code
                        </option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <a href="/dashboard/coupons/create" class="btn btn-primary">
                Create Coupon
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Expire Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($coupons as $coupon)
                        <tr>
                            <td>{{ $coupon->id }}</td>
                            <td>{{ $coupon->code }}</td>
                            @if ($coupon->discount_amount)
                                <td>&pound;{{ $coupon->discount_amount }}</td>
                            @else
                                <td>{{ $coupon->discount_percentage }}%</td>
                            @endif
                            <!-- format date -->
                            <td>{{ date('d-m-Y', strtotime($coupon->expiration_date)) }}</td>
                            <td>{{ $coupon->is_active ? "Active" : "Inactive" }}</td>
                            <td>
                                <a href="/dashboard/coupons/edit/{{ $coupon->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                                        fill="none">
                                        <path
                                            d="M13.1066 7.98L11.9281 6.80148L4.16667 14.5629V15.7414H5.34517L13.1066 7.98ZM14.2851 6.80148L15.4636 5.62297L14.2851 4.44447L13.1066 5.62297L14.2851 6.80148ZM6.03553 17.4081H2.5V13.8726L13.6958 2.6767C14.0213 2.35126 14.5489 2.35126 14.8743 2.6767L17.2314 5.03372C17.5568 5.35916 17.5568 5.88679 17.2314 6.21223L6.03553 17.4081Z"
                                            fill="#2A2929" />
                                    </svg>
                                </a>
                                <a href="/dashboard/coupons/delete/{{ $coupon->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                                        fill="none">
                                        <path
                                            d="M14.166 5.00033H18.3327V6.66699H16.666V17.5003C16.666 17.9606 16.2929 18.3337 15.8327 18.3337H4.16602C3.70578 18.3337 3.33268 17.9606 3.33268 17.5003V6.66699H1.66602V5.00033H5.83268V2.50033C5.83268 2.04009 6.20578 1.66699 6.66602 1.66699H13.3327C13.7929 1.66699 14.166 2.04009 14.166 2.50033V5.00033ZM14.9993 6.66699H4.99935V16.667H14.9993V6.66699ZM7.49935 3.33366V5.00033H12.4993V3.33366H7.49935Z"
                                            fill="#2A2929" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $coupons->links() }}
        </div>
    </div>
</div>

<script>
    document.querySelector('#coupons').classList.add('active');
    document.querySelector('#coupons .nav-link ').classList.add('active');
</script>
@endsection