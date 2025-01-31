<link href="{{ asset('styles/dashboard/navbar.css') }}" rel="stylesheet">

<nav class="navbar">
    <div class="container-fluid">
        <div>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidemenu"
                aria-controls="sidemenu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <div id="navbarContent">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <div class="dropdown notify">
                        <svg data-bs-toggle="dropdown" class="bell {{ $totalUnreadCount ? 'unread' : '' }}"
                            xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36" fill="none">
                            <path
                                d="M29.0099 21.735L27.5099 19.245C27.1949 18.69 26.9099 17.64 26.9099 17.025V13.23C26.9099 9.705 24.8399 6.66 21.8549 5.235C21.0749 3.855 19.6349 3 17.9849 3C16.3499 3 14.8799 3.885 14.0999 5.28C11.1749 6.735 9.14991 9.75 9.14991 13.23V17.025C9.14991 17.64 8.86491 18.69 8.54991 19.23L7.03491 21.735C6.43491 22.74 6.29991 23.85 6.67491 24.87C7.03491 25.875 7.88991 26.655 8.99991 27.03C11.9099 28.02 14.9699 28.5 18.0299 28.5C21.0899 28.5 24.1499 28.02 27.0599 27.045C28.1099 26.7 28.9199 25.905 29.3099 24.87C29.6999 23.835 29.5949 22.695 29.0099 21.735Z"
                                fill="#C99246" />
                            <path
                                d="M22.245 30.015C21.615 31.755 19.95 33 18 33C16.815 33 15.645 32.52 14.82 31.665C14.34 31.215 13.98 30.615 13.77 30C13.965 30.03 14.16 30.045 14.37 30.075C14.715 30.12 15.075 30.165 15.435 30.195C16.29 30.27 17.16 30.315 18.03 30.315C18.885 30.315 19.74 30.27 20.58 30.195C20.895 30.165 21.21 30.15 21.51 30.105C21.75 30.075 21.99 30.045 22.245 30.015Z"
                                fill="#C99246" />
                        </svg>
                        @if ($totalUnreadCount)
                            <div class="counter">
                                {{ $totalUnreadCount > 9 ? '9+' : $totalUnreadCount }}
                            </div>
                        @endif
                        <ul class="dropdown-menu">
                            <div class="name">
                                Notifications
                            </div>
                            @if (count($notifications) == 0)
                                <div class="no-notifications">
                                    No notifications so far.
                                </div>
                            @endif
                            @foreach($notifications as $notification)
                                <a class="dropdown-item {{ $notification->status == 'unread' ? 'new' : '' }}">
                                    <div class="icon">
                                        <img src="{{asset('imgs/icon.png')}}" alt="icon">
                                        {!! $notification->icon ?? "" !!}
                                    </div>
                                    <div class="content">
                                        <p>{{ $notification->content }}</p>
                                        <span>{{ $notification->created_at }}</span>
                                        <span>{{ $notification->created_at->diffForHumans() }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </ul>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button"
                        data-bs-toggle="dropdown">
                        @if (Auth::user()->profile_picture == null)
                            <i class="fas fa-user"></i>
                        @else
                            <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" alt="Profile"
                                class="rounded-circle" width="30">
                        @endif
                        {{ Auth::user()->first_name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li>
                            <a class="dropdown-item" href="/dashboard">Home</a>
                        </li>
                        <li><a class="dropdown-item" href="#"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>