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
        Testimonials
    </h3>
    <hr>
    <div class="content">
        <div class="left">
            <a href="/dashboard/home/hero">Hero Banner</a>
            <a href="/dashboard/home/how">How It Works</a>
            <a href="/dashboard/home/events">Events</a>
            <a href="/dashboard/home/why">Why Choose Us</a>
            <a href="/dashboard/home/first">First Divider Section</a>
            <a href="/dashboard/home/testimonials" class="active">Testimonials</a>
            <a href="/dashboard/home/faq">FAQs</a>
            <!-- <a href="/dashboard/home/second">Second Divider Section</a> -->
        </div>
        <div class="right">
            <a href="/dashboard/blog/blogs/create" class="btn btn-primary">
                Create Blog
            </a>
            <div class="table-responsive">
            </div>
            <div class="pagination-container d-flex justify-content-between align-items-center"></div>
        </div>
    </div>
</div>

<script>
    document.querySelector('#home').classList.add('active');
    document.querySelector('#home .nav-link ').classList.add('active');
</script>
@endsection