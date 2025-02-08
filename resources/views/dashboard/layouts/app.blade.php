<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta property="og:image" content="{{ asset("imgs/logo.png") }}" />

    <title>@yield('title', tt('Marasem') . ' | ' . tt('Dashboard'))</title>
    <link rel="shortcut icon" href="{{ asset("imgs/logo.png") }}">
    <!-- Default CSS -->
    <div>
        <!-- bootstrap -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css"
            crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- aos -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css"
            integrity="sha512-1cK78a1o+ht2JcaW6g8OXYwqpev9+6GqOkz9xmBN9iUUhIndKtxwILGWYOSibOKjLsEdjyjZvYDq/cZwNeak0w=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- jquery -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
            integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <!-- font awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
            integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- google fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap"
            rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
        <link
            href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
            rel="stylesheet">
        <link
            href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
            rel="stylesheet">
        <link
            href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Noto+Sans+Arabic:wght@100..900&display=swap"
            rel="stylesheet">
        <!-- splide -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/splidejs/4.1.4/css/splide.min.css"
            integrity="sha512-KhFXpe+VJEu5HYbJyKQs9VvwGB+jQepqb4ZnlhUF/jQGxYJcjdxOTf6cr445hOc791FFLs18DKVpfrQnONOB1g=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- tel -->
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
        <!-- select2 -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    </div>

    <link href="{{ asset('styles/root.css') }}" rel="stylesheet">
    <link href="{{ asset('styles/dashboard/app.css') }}" rel="stylesheet">
    @yield('css')
    <link href="{{ asset('styles/dashboard/bookings.css') }}" rel="stylesheet">
    <link href="{{ asset('styles/dashboard/coupons.css') }}" rel="stylesheet">
    @if (auth()->user()->language->code == 'ar')
        <style>
            html {
                direction: rtl;
            }

            .navbar-nav .dropdown-menu {
                right: -50px;
            }

            .notify .dropdown-menu {
                right: -150px;
            }
        </style>
    @endif
</head>

<body>
    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ tt(session('error')) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ tt($error) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ tt(session('success')) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php    session()->forget('success'); ?>
    @endif

    <div class="body">
        @include('dashboard.layouts.sidebar')

        <div class="main">
            @include('dashboard.layouts.navbar')

            <main class="flex-grow-1 p-4">
                @yield('content')
            </main>
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
        <script>
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
        </script>
        <!-- aos -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"
            integrity="sha512-A7AYk1fGKX6S2SsHywmPkrnzTZHrgiVT7GcQkLGDe2ev0aWb8zejytzS8wjo7PGEXKqJOrjQ4oORtnimIRZBtw=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            AOS.init();
        </script>
        <!-- splide -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/splidejs/4.1.4/js/splide.min.js"
            integrity="sha512-4TcjHXQMLM7Y6eqfiasrsnRCc8D/unDeY1UGKGgfwyLUCTsHYMxF7/UHayjItKQKIoP6TTQ6AMamb9w2GMAvNg=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <!-- phone -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
        <!-- select2 -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    </div>
    <!-- validation -->
    <script>
        $(document).ready(function () {
            // Loop through all forms and disable native validation
            $("form").each(function () {
                $(this).attr("novalidate", "novalidate");
            });
            $("form").on("submit", function (e) {
                var $form = $(this);
                var missingFields = [];
                // Loop through all required fields inside this form
                $form.find("input[required], textarea[required], select[required]").each(function () {
                    var $field = $(this);
                    // Check if the field value is empty after trimming whitespace
                    if ($.trim($field.val()) === "") {
                        // Identify the field by its name or id, or fallback to a generic label
                        var identifier = $field.attr("name") || $field.attr("id") || "a required field";
                        missingFields.push(identifier);
                    }
                });
                // If any required fields are missing, prevent form submission and alert the user
                if (missingFields.length > 0) {
                    e.preventDefault();
                    alert("Please fill out the following required fields: " + missingFields.join(", "));
                }
            });
        });

        // select2
        $('select').each(function () {
            $(this).select2({
                placeholder: $(this).data('placeholder') || 'Select an option',
                // allowClear: true,
                minimumResultsForSearch: 0  // Forces the search box to always be visible
            });
        });
    </script>
    @yield('scripts')
    <!-- language -->
    <script>
        function updateLang() {
            // Get the selected locale value from the select element
            let locale = document.querySelector('[name=locale]').value;
            $.ajax({
                url: '{{ route('dashboard.change.language') }}',
                type: 'POST',
                data: {
                    locale: locale,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    // On success, reload the page
                    location.reload();
                },
                error: function (xhr) {
                    // On error, show a Bootstrap toast for 3 seconds
                    showToast("{{ tt('Failed to update locale.') }}", 'danger', 3000);
                }
            });
        }

        // Function to display a Bootstrap toast message
        function showToast(message, type = 'success', duration = 3000) {
            // Create a toast element if one doesn't already exist
            let toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toastContainer';
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }

            // Create the toast element
            let toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');
            toastEl.style.minWidth = '250px';

            // Build toast inner HTML
            toastEl.innerHTML = `
                                    <div class="d-flex">
                                        <div class="toast-body">${message}</div>
                                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                    </div>
                                `;

            // Append the toast to the container
            toastContainer.appendChild(toastEl);

            // Initialize and show the toast using Bootstrap's JS API
            let toast = new bootstrap.Toast(toastEl, { delay: duration });
            toast.show();

            // Remove the toast element from the DOM after it hides
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
        }

    </script>
</body>

</html>