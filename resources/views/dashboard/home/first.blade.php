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
        First Divider
    </h3>
    <hr>
    <div class="content">
        <div class="left">
            <a href="/dashboard/home/hero">Hero Banner</a>
            <a href="/dashboard/home/how">How It Works</a>
            <a href="/dashboard/home/events">Events</a>
            <a href="/dashboard/home/why">Why Choose Us</a>
            <a href="/dashboard/home/first" class="active">First Divider Section</a>
            <!-- <a href="/dashboard/home/testimonials">Testimonials</a> -->
            <a href="/dashboard/home/faq">FAQs</a>
            <!-- <a href="/dashboard/home/second">Second Divider Section</a> -->
        </div>
        <div class="right">
            <form action="{{ route('home.updateFirstDivider') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-floating">
                    <textarea class="form-control" name="title" placeholder="Enter title"
                        id="title">{{ $homePageSection->first_divider_title ?? '' }}</textarea>
                    <label for="title">Title</label>
                </div>
                <div class="form-floating">
                    <textarea class="form-control" name="second" placeholder="Enter description"
                        id="second">{{ $homePageSection->first_divider_desc ?? '' }}</textarea>
                    <label for="second">Description</label>
                </div>
                <div class="form-group">
                    <label for="photo">Background Photo</label>
                    <small>Select photo to update current</small>
                    <input class="form-control" type="file" id="photo" name="photo">
                    @if(!empty($homePageSection->first_divider_photo))
                        <img src="{{ asset('storage/' . $homePageSection->first_divider_photo) }}" width="200" height="200"
                            alt="Current Photo">
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