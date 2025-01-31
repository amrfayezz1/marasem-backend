@extends('dashboard.layouts.app')

<link href="{{ asset('styles/dashboard/categories.css') }}" rel="stylesheet">
<link href="{{ asset('styles/dashboard/blogs.css') }}" rel="stylesheet">
<!-- <link href="{{ asset('styles/dashboard/home.css') }}" rel="stylesheet"> -->
@section('content')
<div class="container home">
    <h3>
        Registered Users
    </h3>
    <hr>
    <div class="content">
        <div class="d-flex justify-content-between">
            <form action="{{ route('users.search') }}" method="GET" class="d-flex">
                <!-- reset -->
                @if (isset($_GET['query']) || isset($_GET['filter']))
                    <a href="{{ route('users.index') }}" class="btn btn-secondary me-2">Reset</a>
                @endif
                <div class="input-group">
                    <input type="text" name="query" class="form-control" aria-label="Search..." placeholder="Search..."
                        required style="flex: 3;" value="{{ isset($_GET['query']) ? $_GET['query'] : '' }}">
                    <select name="filter" class="form-select me-2" required>
                        <option value="id" {{ isset($_GET['filter']) && $_GET['filter'] == 'id' ? 'selected' : '' }}>ID
                        </option>
                        <option value="name" {{ isset($_GET['filter']) && $_GET['filter'] == 'name' ? 'selected' : '' }}>
                            Name
                        </option>
                        <option value="email" {{ isset($_GET['filter']) && $_GET['filter'] == 'email' ? 'selected' : '' }}>
                            Email</option>
                        <option value="phone" {{ isset($_GET['filter']) && $_GET['filter'] == 'phone' ? 'selected' : '' }}>
                            Phone</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Date Joined</th>
                        <th scope="col">Wallet Amount</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone }}</td>
                            <td>{{ \Carbon\Carbon::parse($user->created_at)->format('d M, Y') }}</td>
                            <td>&pound;{{ $user->wallet_amount }}</td>
                            <td>
                                <a href="/dashboard/users/{{ $user->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="16" viewBox="0 0 20 16"
                                        fill="none">
                                        <path
                                            d="M9.99993 0.5C14.4933 0.5 18.2317 3.73313 19.0154 8C18.2317 12.2668 14.4933 15.5 9.99993 15.5C5.50644 15.5 1.76813 12.2668 0.984375 8C1.76813 3.73313 5.50644 0.5 9.99993 0.5ZM9.99993 13.8333C13.5296 13.8333 16.5499 11.3767 17.3144 8C16.5499 4.62336 13.5296 2.16667 9.99993 2.16667C6.47018 2.16667 3.44986 4.62336 2.68533 8C3.44986 11.3767 6.47018 13.8333 9.99993 13.8333ZM9.99993 11.75C7.92883 11.75 6.24989 10.0711 6.24989 8C6.24989 5.92893 7.92883 4.25 9.99993 4.25C12.0709 4.25 13.7499 5.92893 13.7499 8C13.7499 10.0711 12.0709 11.75 9.99993 11.75ZM9.99993 10.0833C11.1505 10.0833 12.0833 9.15058 12.0833 8C12.0833 6.84942 11.1505 5.91667 9.99993 5.91667C8.84934 5.91667 7.91656 6.84942 7.91656 8C7.91656 9.15058 8.84934 10.0833 9.99993 10.0833Z"
                                            fill="#2A2929" />
                                    </svg>
                                </a>
                            </td>
                    @endforeach
                </tbody>
            </table>
            {{ $users->links() }}
        </div>
    </div>
</div>

<script>
    document.querySelector('#users').classList.add('active');
    document.querySelector('#users .nav-link ').classList.add('active');
</script>
@endsection