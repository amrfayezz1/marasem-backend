@extends('dashboard.layouts.app')

@section('content')
<link href="{{ asset('styles/dashboard/create.css') }}" rel="stylesheet">
<link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
<div class="container booking">
    <h3>Create Booking</h3>
    <hr>
    <div class="order">
        <form action="{{ route('bookings.store') }}" method="POST">
            @csrf
            <!-- Booking Source and Driver Details -->
            <div class="form-group mb-2">
                <label for="source">Booking Source</label>
                <select name="source" id="source" class="form-control" required>
                    <option value="" disabled selected>Select a source</option>
                    <option value="website">Website</option>
                    <option value="TBMS">TBMS</option>
                </select>
            </div>
            <!-- Car Type Section -->
            <div class="car">
                <h4>Car</h4>
                <div class="form-group">
                    <label for="car_type">Car Type</label>
                    <select name="car_type" id="car_type" class="form-select select2" required>
                        <option value="" disabled selected>Select a car type</option>
                        @foreach ($car_types as $car)
                            <option value="{{ $car->id }}" data-image="{{ asset($car->car_img) }}">{{ $car->type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="data">
                    <div class="form-group">
                        <label for="driver_name">Driver Name</label>
                        <input type="text" name="driver_name" id="driver_name" class="form-control"
                            placeholder="Enter driver's name">
                    </div>
                    <div class="form-group">
                        <label for="driver_phone">Driver Phone <small>(include country code)</small></label>
                        <input type="text" name="driver_phone" id="driver_phone" class="form-control"
                            placeholder="Enter driver's phone">
                    </div>
                </div>
            </div>
            <hr>
            <!-- Booking Details Section -->
            <div class="travel">
                <h4>Booking Details</h4>
                <div class="form-group">
                    <label for="total_price">Total Price</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text">&pound;</span>
                        <input type="text" class="form-control" aria-label="Amount" name="total_price" placeholder="Amount" required>
                    </div>
                </div>
                <div class="data">
                    <div class="form-group">
                        <label for="pickup_location">Pick Up</label>
                        <input type="text" name="pickup_location" id="pickup_location" class="form-control"
                            placeholder="Enter pick-up location" required>
                    </div>
                    <div class="form-group">
                        <label for="dropoff_location">Drop Off</label>
                        <input type="text" name="dropoff_location" id="dropoff_location" class="form-control"
                            placeholder="Enter drop-off location" required>
                    </div>
                </div>
            </div>
            <div class="data">
                <div class="form-group">
                    <label for="booking_date">Date</label>
                    <input type="datetime-local" name="booking_date" id="booking_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="flight_number">Flight Number</label>
                    <input type="text" name="flight_number" id="flight_number" class="form-control"
                        placeholder="Enter flight number">
                </div>
            </div>

            <div class="data">
                <div class="form-group">
                    <label for="passengers">Passengers</label>
                    <input type="number" name="passenger_adults" class="form-control mb-2"
                        placeholder="Number of Adults" required>
                </div>
                <div class="form-group">
                    <input type="number" name="passenger_children" class="form-control mb-2"
                        placeholder="Number of Children" required>
                </div>
                <div class="form-group">
                    <input type="number" name="passenger_infants" class="form-control mb-2"
                        placeholder="Number of Infants" required>
                </div>
            </div>
            <hr>
            <!-- Passenger Details Section -->
            <div class="pass mb-5">
                <h4>Passenger Details</h4>
                <div class="data">
                    <div class="form-group">
                        <label for="passenger_name">Name</label>
                        <input type="text" name="passenger_name" id="passenger_name" class="form-control"
                            placeholder="Enter passenger name" required>
                    </div>
                    <div class="form-group">
                        <label for="passenger_surname">Surname</label>
                        <input type="text" name="passenger_surname" id="passenger_surname" class="form-control"
                            placeholder="Enter passenger surname">
                    </div>
                </div>
                <div class="data">
                    <div class="form-group">
                        <label for="passenger_phone">Phone <small>(include country code)</small></label>
                        <input type="text" name="passenger_phone" id="passenger_phone" class="form-control"
                            placeholder="Enter passenger phone" required>
                    </div>
                    <div class="form-group">
                        <label for="passenger_email">Email</label>
                        <input type="email" name="passenger_email" id="passenger_email" class="form-control"
                            placeholder="Enter passenger email">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">Submit Booking</button>
        </form>
    </div>
</div>

<script>
    document.querySelector('#bookings').classList.add('active');
    document.querySelector('#bookings .nav-link').classList.add('active');
</script>
<script>
    $(document).ready(function () {
        function formatOption(option) {
            if (!option.id) {
                return option.text;
            }

            const imgUrl = $(option.element).data('image');
            const template = `
                <div style="display: flex; align-items: center;">
                    <img src="${imgUrl}" style="width: 70px; margin-right: 10px;" />
                    ${option.text}
                </div>`;
            return $(template);
        }

        $('#car_type').select2({
            templateResult: formatOption,
            templateSelection: formatOption,
            theme: 'bootstrap-5',
            placeholder: 'Select a car type',
            allowClear: true,
        });
    });
</script>

@endsection