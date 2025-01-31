@extends('dashboard.layouts.app')

@section('content')
<div class="container">
    <h3>Edit FAQ</h3>
    <form action="{{ route('faq.update', $faq->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group mt-3">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" class="form-control" value="{{ $faq->title }}" required>
        </div>
        <div class="form-group mt-3">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" rows="5" required>{{ $faq->description }}</textarea>
        </div>
        <div class="form-group mt-3">
            <label for="featured_home">Feature on Home Page</label>
            <select name="featured_home" id="featured_home" class="form-select" required>
                <option value="0" {{ !$faq->featured_home ? 'selected' : '' }}>No</option>
                <option value="1" {{ $faq->featured_home ? 'selected' : '' }}>Yes</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Update</button>
    </form>
</div>
@endsection
