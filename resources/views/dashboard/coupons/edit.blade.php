@extends('dashboard.layouts.app')

<link href="{{ asset('styles/dashboard/create.css') }}" rel="stylesheet">

@section('content')
<div class="container categories">
    <h3>
        Coupons
        <div>
            <i class="fa-solid fa-chevron-right"></i><i class="fa-solid fa-chevron-right"></i>
        </div>
        Edit Coupon
    </h3>
    <hr>
    <div class="content">
        <form action="{{route('update.coupons', $coupon->id)}}" method="post">
            <h4>Edit Coupon</h4>
            <hr>
            @csrf
            @method('POST') <!-- Assuming you use POST for updates -->
            <div class="form-group mt-3">
                <label for="code">Code</label>
                <input type="text" name="code" id="code" class="form-control" value="{{ $coupon->code }}" required>
            </div>

            <div class="form-group mt-3">
                <label for="discount_amount">Discount Amount</label>
                <input type="number" step="0.01" name="discount_amount" id="discount_amount" class="form-control"
                    value="{{ $coupon->discount_amount }}">
            </div>

            <div class="form-group mt-3">
                <label for="discount_percentage">Discount Percentage</label>
                <input type="number" step="0.01" name="discount_percentage" id="discount_percentage"
                    class="form-control" value="{{ $coupon->discount_percentage }}">
            </div>

            <div class="form-group mt-3">
                <label for="expiration_date">Expiration Date</label>
                <input type="date" name="expiration_date" id="expiration_date" class="form-control"
                    value="{{ $coupon->expiration_date }}" required>
            </div>

            <div class="form-group mt-3">
                <label for="max_uses">Max Uses</label>
                <input type="number" name="max_uses" id="max_uses" class="form-control" value="{{ $coupon->max_uses }}"
                    required>
            </div>

            <div class="form-group mt-3">
                <label for="user_id">Assign to User (Optional)</label>
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">All Users</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ $coupon->user_id == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mt-3">
                <label for="is_active">Active</label>
                <select name="is_active" id="is_active" class="form-control" required>
                    <option value="1" {{ $coupon->is_active == 1 ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ $coupon->is_active == 0 ? 'selected' : '' }}>No</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Update</button>
        </form>
    </div>
</div>

<script>
    document.querySelector('#coupons').classList.add('active');
    document.querySelector('#coupons .nav-link ').classList.add('active');
</script>
@endsection