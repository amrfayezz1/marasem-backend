@extends('dashboard.layouts.app')

@section('content')
<div class="container">
    <h3>Edit Event</h3>
    <form action="{{ route('event.update', $event->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-group mt-3">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" class="form-control" value="{{ $event->title }}">
        </div>

        <div class="form-group mt-3">
            <label for="date">Event Start Date</label>
            <input type="date" name="date" id="date" class="form-control" value="{{ $event->date }}">
        </div>

        <div class="form-group mt-3">
            <label for="date_end">Event End Date</label>
            <input type="date" name="date_end" id="date_end" class="form-control" value="{{ $event->date_end }}">
        </div>

        <div class="form-group mt-3">
            <label for="time_begin">Start Time</label>
            <input type="time" name="time_begin" id="time_begin" class="form-control" value="{{ $event->time_begin }}">
        </div>

        <div class="form-group mt-3">
            <label for="time_end">End Time</label>
            <input type="time" name="time_end" id="time_end" class="form-control" value="{{ $event->time_end }}">
        </div>

        <div class="form-group mt-3">
            <label for="location">Location</label>
            <input type="text" name="location" id="location" class="form-control" value="{{ $event->location }}">
        </div>

        <div class="form-group mt-3">
            <label for="image_path">Event Image</label>
            <input type="file" name="image_path" id="image_path" class="form-control">
            <img src="{{ asset('storage/' . $event->image_path) }}" width="200" height="200" alt="">
        </div>

        <div class="form-group mt-3">
            <label for="category">Category</label>
            <select name="category" id="category" class="form-select">
                <option value="Upcoming" {{ $event->category == 'Upcoming' ? 'selected' : '' }}>Upcoming</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Update Event</button>
    </form>
</div>
@endsection
