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
        FAQs
    </h3>
    <hr>
    <div class="content">
        <div class="left">
            <a href="/dashboard/home/hero">Hero Banner</a>
            <a href="/dashboard/home/how">How It Works</a>
            <a href="/dashboard/home/events">Events</a>
            <a href="/dashboard/home/why">Why Choose Us</a>
            <a href="/dashboard/home/first">First Divider Section</a>
            <!-- <a href="/dashboard/home/testimonials">Testimonials</a> -->
            <a href="/dashboard/home/faq" class="active">FAQs</a>
            <!-- <a href="/dashboard/home/second">Second Divider Section</a> -->
        </div>
        <div class="right">
            <a href="/dashboard/blog/blogs/create" class="btn btn-primary">
                Create Blog
            </a>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Featured</th>
                            <th scope="col">Title</th>
                            <th scope="col">Category</th>
                            <th scope="col">Date</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($blogs as $blog)
                            <tr>
                                <td>{{ $blog->id }}</td>
                                <td>{{ $blog->featured ? 'Yes' : 'No' }}</td>
                                <td>{{ $blog->title }}</td>
                                <td>{{ App\Models\Category::find($blog->category)->category ?? '' }}</td>
                                <td>{{ $blog->date }}</td>
                                <td>
                                    <a href="/dashboard/blog/blogs/edit/{{ $blog->id }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                                            fill="none">
                                            <path
                                                d="M13.1066 7.98L11.9281 6.80148L4.16667 14.5629V15.7414H5.34517L13.1066 7.98ZM14.2851 6.80148L15.4636 5.62297L14.2851 4.44447L13.1066 5.62297L14.2851 6.80148ZM6.03553 17.4081H2.5V13.8726L13.6958 2.6767C14.0213 2.35126 14.5489 2.35126 14.8743 2.6767L17.2314 5.03372C17.5568 5.35916 17.5568 5.88679 17.2314 6.21223L6.03553 17.4081Z"
                                                fill="#2A2929" />
                                        </svg>
                                    </a>
                                    <a href="/blogs/{{ $blog->id }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="16" viewBox="0 0 20 16"
                                            fill="none">
                                            <path
                                                d="M9.99993 0.5C14.4933 0.5 18.2317 3.73313 19.0154 8C18.2317 12.2668 14.4933 15.5 9.99993 15.5C5.50644 15.5 1.76813 12.2668 0.984375 8C1.76813 3.73313 5.50644 0.5 9.99993 0.5ZM9.99993 13.8333C13.5296 13.8333 16.5499 11.3767 17.3144 8C16.5499 4.62336 13.5296 2.16667 9.99993 2.16667C6.47018 2.16667 3.44986 4.62336 2.68533 8C3.44986 11.3767 6.47018 13.8333 9.99993 13.8333ZM9.99993 11.75C7.92883 11.75 6.24989 10.0711 6.24989 8C6.24989 5.92893 7.92883 4.25 9.99993 4.25C12.0709 4.25 13.7499 5.92893 13.7499 8C13.7499 10.0711 12.0709 11.75 9.99993 11.75ZM9.99993 10.0833C11.1505 10.0833 12.0833 9.15058 12.0833 8C12.0833 6.84942 11.1505 5.91667 9.99993 5.91667C8.84934 5.91667 7.91656 6.84942 7.91656 8C7.91656 9.15058 8.84934 10.0833 9.99993 10.0833Z"
                                                fill="#2A2929" />
                                        </svg>
                                    </a>
                                    <a href="/dashboard/blog/blogs/delete/{{ $blog->id }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                                            fill="none">
                                            <path
                                                d="M14.166 5.00033H18.3327V6.66699H16.666V17.5003C16.666 17.9606 16.2929 18.3337 15.8327 18.3337H4.16602C3.70578 18.3337 3.33268 17.9606 3.33268 17.5003V6.66699H1.66602V5.00033H5.83268V2.50033C5.83268 2.04009 6.20578 1.66699 6.66602 1.66699H13.3327C13.7929 1.66699 14.166 2.04009 14.166 2.50033V5.00033ZM14.9993 6.66699H4.99935V16.667H14.9993V6.66699ZM7.49935 3.33366V5.00033H12.4993V3.33366H7.49935Z"
                                                fill="#2A2929" />
                                        </svg>
                                    </a>
                                </td>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-container d-flex justify-content-between align-items-center">
                <span>
                    Showing {{ $blogs->firstItem() }}-{{ $blogs->lastItem() }} of {{ $blogs->total() }}
                </span>
                <div class="pagination-buttons">
                    @if ($blogs->onFirstPage())
                        <button class="btn btn-secondary" disabled>&lt;</button>
                    @else
                        <a href="{{ $blogs->previousPageUrl() }}" class="btn btn-primary">&lt;</a>
                    @endif

                    @if ($blogs->hasMorePages())
                        <a href="{{ $blogs->nextPageUrl() }}" class="btn btn-primary">&gt;</a>
                    @else
                        <button class="btn btn-secondary" disabled>&gt;</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelector('#home').classList.add('active');
    document.querySelector('#home .nav-link ').classList.add('active');
</script>
@endsection