@extends('dashboard.layouts.app')

@section('content')
<div class="container">
    <h3>Create Event</h3>
    <form action="{{ route('event.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group mt-3">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}">
        </div>

        <div class="form-group mt-3">
            <label for="date">Event Start Date</label>
            <input type="date" name="date" id="date" class="form-control" value="{{ old('date') }}">
        </div>

        <div class="form-group mt-3">
            <label for="date_end">Event End Date</label>
            <input type="date" name="date_end" id="date_end" class="form-control" value="{{ old('date_end') }}">
        </div>

        <div class="form-group mt-3">
            <label for="time_begin">Start Time</label>
            <input type="time" name="time_begin" id="time_begin" class="form-control" value="{{ old('time_begin') }}">
        </div>

        <div class="form-group mt-3">
            <label for="time_end">End Time</label>
            <input type="time" name="time_end" id="time_end" class="form-control" value="{{ old('time_end') }}">
        </div>

        <div class="form-group mt-3">
            <label for="location">Location</label>
            <input type="text" name="location" id="location" class="form-control" value="{{ old('location') }}">
        </div>

        <div class="form-group mt-3">
            <label for="image_path">Event Image</label>
            <input type="file" name="image_path" id="image_path" class="form-control">
        </div>

        <div class="form-group mt-3">
            <label for="category">Category</label>
            <select name="category" id="category" class="form-select">
                <option value="Upcoming">Upcoming</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Create Event</button>
    </form>
</div>
@endsection
