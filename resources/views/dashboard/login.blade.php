<!DOCTYPE html>
<html lang="en">
@php
    if (session('locale') || Session::get('locale')) {
        $locale = session('locale') ?? Session::get('locale');
    } elseif (isset($_REQUEST['locale']) && $_REQUEST['locale']) {
        $locale = $_REQUEST['locale'];
    } else {
        $locale = 'en';
    }
    if ($locale == 'ar') {
        echo '<style>body {direction: rtl;}</style>';
    }
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', tt('Marasem', $locale) . ' | ' . tt('Login', $locale))</title>
    <link rel="shortcut icon" href="{{ asset("imgs/logo.png") }}" type="image/x-icon">
    <!-- Default CSS -->
    <div>
        <!-- bootstrap -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css"
            crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- jquery -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
            integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <!-- google fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
        <!-- font awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
            integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- tel -->
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
        <!-- select2 -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    </div>
    <link rel="stylesheet" href="{{ asset('styles/root.css')}} ">
    <link rel="stylesheet" href="{{ asset('styles/register.css')}} ">
</head>

<body>
    <div class="containers">
        <div class="wrap">
            <div class="head">
                <img src="{{ asset('imgs/logo.png') }}" alt="Logo" width="50px">
            </div>
            <form method="POST" action="{{ route('admin.signin') }}">
                <h1>{{ tt('Welcome to Marasem', $locale) }}</h1>
                @csrf
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach ($errors->all() as $error)
                            <li>{{ tt($error, $locale) }}</li>
                        @endforeach
                    </div>
                @endif
                <div class="form-floating">
                    <input class="form-control" id="email" type="email" name="email" placeholder=" "
                        value="{{ old('email') }}" required autocomplete="email" autofocus>
                    <label for="email">{{ tt('Email', $locale) }}</label>
                </div>

                <div class="input-icon">
                    <div class="form-floating">
                        <input class="form-control" id="password" type="password" name="password" required
                            placeholder=" " autocomplete="current-password">
                        <i class="fas fa-eye-slash" id="password-icon"></i>
                        <label for="password">{{ tt('Password', $locale) }}</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ tt('Sign In', $locale) }}</button>
            </form>
        </div>
        <div class="img">
            <img src="{{ asset('imgs/loginSide.jpg') }}" alt="art canvas">
        </div>
    </div>

    <!-- scripts -->
    <div>
        <!-- bootstrap -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"
            integrity="sha512-7Pi/otdlbbCR+LnW+F7PwFcSDJOuUJB3OxtEHbg4vSMvzvJjde4Po1v4BR9Gdc9aXNUNFVUY+SK51wWT8WF0Gg=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <!-- popper -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js"
            integrity="sha512-TPh2Oxlg1zp+kz3nFA0C5vVC6leG/6mm1z9+mA81MI5eaUVqasPLO8Cuk4gMF4gUfP5etR73rgU/8PNMsSesoQ=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <!-- phone -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
        <!-- select2 -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    </div>
    <script>
        const togglePassword = document.getElementById('password-icon');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            // Check if the password is currently visible
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            // Toggle the icon class
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>