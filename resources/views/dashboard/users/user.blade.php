@extends('dashboard.layouts.app')
@section('css')
<link href="{{ asset('styles/dashboard/categories.css') }}" rel="stylesheet">
<link href="{{ asset('styles/dashboard/blogs.css') }}" rel="stylesheet">
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
@endsection
@section('content')
<div class="container home bookings">
    <h3>
        Registered Users
        <div>
            <i class="fa-solid fa-chevron-right"></i><i class="fa-solid fa-chevron-right"></i>
        </div>
        User Account
    </h3>
    <hr>
    <div class="d-flex justify-content-between">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#ongoing">Ongoing</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#past">Past</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#asap">ASAP</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#as">As Directed</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#wallet">Wallet</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#coupon">Coupons</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#feedbacks">Feedbacks</a>
            </li>
        </ul>
    </div>

    <div class="tab-content mt-3">
        <!-- Ongoing Bookings -->
        <div id="ongoing" class="tab-pane fade show active">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pickup time</th>
                        <th>Pickup</th>
                        <th>Dropoff</th>
                        <th>Status</th>
                        <th>Car Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ongoingBookings as $booking)
                        <tr>
                            <td>{{ $booking->id }}</td>
                            <!-- format date -->
                            <td>{{ date('d-m-Y H:i A', strtotime($booking->pickup_time)) }}</td>
                            <td>{{ $booking->pickup_location }}</td>
                            <td>{{ $booking->dropoff_location }}</td>
                            <td>{{ $booking->status }}</td>
                            <td>{{ $booking->car->type }}</td>
                            <td>
                                <a href="{{ route('bookings.edit', $booking->id) }}" class="btn btn-sm btn-warning">Update
                                    Status</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $ongoingBookings->links() }}
        </div>

        <!-- Past Bookings -->
        <div id="past" class="tab-pane fade">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pickup time</th>
                        <th>Pickup</th>
                        <th>Dropoff</th>
                        <th>Status</th>
                        <th>Car Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pastBookings as $booking)
                        <tr>
                            <td>{{ $booking->id }}</td>
                            <!-- format date -->
                            <td>{{ date('d-m-Y H:i A', strtotime($booking->pickup_time)) }}</td>
                            <td>{{ $booking->pickup_location }}</td>
                            <td>{{ $booking->dropoff_location }}</td>
                            <td>{{ $booking->status }}</td>
                            <td>{{ $booking->car->type }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $pastBookings->links() }}
        </div>

        <!-- ASAP Bookings -->
        <div id="asap" class="tab-pane fade">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Phone</th>
                        <th>Date Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($asapBookings as $booking)
                        <tr>
                            <td>{{ $booking->id }}</td>
                            <td>{{ $booking->phone }}</td>
                            <!-- format date -->
                            <td>{{ date('d-m-Y H:i A', strtotime($booking->created_at)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $asapBookings->links() }}
        </div>

        <!-- AS Bookings -->
        <div id="as" class="tab-pane fade">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Phone</th>
                        <th>Pickup time</th>
                        <th>Pickup</th>
                        <th>Stops</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($asDirectedBookings as $booking)
                        <tr>
                            <td>{{ $booking->id }}</td>
                            <td>{{ $booking->phone }}</td>
                            <!-- format date -->
                            <td>{{ date('d-m-Y H:i A', strtotime($booking->pickup_time)) }}</td>
                            <td>{{ $booking->pickup }}</td>
                            <td>
                                @foreach (json_decode($booking->stops) as $stop)
                                    <li>{{ $stop }}</li>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $asDirectedBookings->links() }}
        </div>

        <!-- wallet -->
        <div id="wallet" class="tab-pane fade">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Reason</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>New Wallet Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($wallet as $w)
                        <tr>
                            <td>{{ $w->id }}</td>
                            <td>{{ $w->reason }}</td>
                            <td>{{ $w->credit_amount }}</td>
                            <!-- format date -->
                            <td>{{ date('d-m-Y H:i A', strtotime($w->created_at)) }}</td>
                            <td>{{ $w->total_credit_after }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- coupons -->
        <div id="coupon" class="tab-pane fade">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Expire Date</th>
                        <th>Is Used</th>
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
                            <td>{{ $coupon->is_used ? "Yes" : "No" }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- feedbacks -->
        <div id="feedbacks" class="tab-pane fade">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Trip ID</th>
                        <th>Rating <small>(out of 5)</small></th>
                        <th>Comment</th>
                        <th>Tips</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($feedbacks as $feedback)
                        <tr>
                            <td>{{ $feedback->id }}</td>
                            <td>{{ $feedback->order_id }}</td>
                            <td>{{ $feedback->rating }}</td>
                            <td>{{ $feedback->comment }}</td>
                            <td>&pound;{{ $feedback->tip ?? 0 }}</td>
                            <!-- format date -->
                            <td>{{ date('d-m-Y', strtotime($feedback->created_at)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.querySelector('#users').classList.add('active');
    document.querySelector('#users .nav-link ').classList.add('active');
</script>
@endsection