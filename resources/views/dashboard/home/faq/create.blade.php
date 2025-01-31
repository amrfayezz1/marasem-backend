@extends('dashboard.layouts.app')

@section('content')
<div class="container">
    <h3>Add New FAQ</h3>
    <form action="{{ route('faq.store') }}" method="POST">
        @csrf
        <div class="form-group mt-3">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" class="form-control" required>
        </div>
        <div class="form-group mt-3">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" rows="5" required></textarea>
        </div>
        <div class="form-group mt-3">
            <label for="featured_home">Feature on Home Page</label>
            <select name="featured_home" id="featured_home" class="form-select" required>
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Save</button>
    </form>
</div>
@endsection
