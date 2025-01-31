@extends('dashboard.layouts.app')

<link href="{{ asset('styles/dashboard/categories.css') }}" rel="stylesheet">
<link href="{{ asset('styles/dashboard/blogs.css') }}" rel="stylesheet">
<link href="{{ asset('styles/dashboard/home.css') }}" rel="stylesheet">

@section('content')
<div class="container home">
    <h3>
        CMS
        <div>
            <i class="fa-solid fa-chevron-right"></i><i class="fa-solid fa-chevron-right"></i>
        </div>
        Home
        <div>
            <i class="fa-solid fa-chevron-right"></i><i class="fa-solid fa-chevron-right"></i>
        </div>
        Hero
    </h3>
    <hr>
    <div class="content">
        <div class="left">
            <a href="/dashboard/home/hero" class="active">Hero Banner</a>
            <a href="/dashboard/home/how">How It Works</a>
            <a href="/dashboard/home/events">Events</a>
            <a href="/dashboard/home/why">Why Choose Us</a>
            <a href="/dashboard/home/first">First Divider Section</a>
            <!-- <a href="/dashboard/home/testimonials">Testimonials</a> -->
            <a href="/dashboard/home/faq">FAQs</a>
            <!-- <a href="/dashboard/home/second">Second Divider Section</a> -->
        </div>
        <div class="right">
            <form action="{{ route('home.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-floating">
                    <textarea class="form-control" name="first" placeholder="Leave a comment here" id="first">{{ $homePageSection->header_first_description ?? '' }}</textarea>
                    <label for="first">First Small Description</label>
                </div>
                <div class="form-floating">
                    <textarea class="form-control" name="second" placeholder="Leave a comment here" id="second">{{ $homePageSection->header_second_description ?? '' }}</textarea>
                    <label for="second">Second Small Description</label>
                </div>
                <div class="form-group">
                    <label for="photo">Header Photo</label>
                    <small>Select photo to update current</small>
                    <input class="form-control" type="file" id="photo" name="photo">
                    @if (!empty($homePageSection->header_photo_path))
                        <img src="{{ asset('storage/' . $homePageSection->header_photo_path) }}" width="200" height="200"
                            alt="">
                    @endif
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.querySelector('#home').classList.add('active');
    document.querySelector('#home .nav-link ').classList.add('active');
</script>
@endsection