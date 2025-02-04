<link href="{{ asset('styles/dashboard/sidebar.css') }}" rel="stylesheet">

<aside id="sidebarContent">
    <div class="d-flex justify-content-center align-items-center">
        <h5 class="mb-0 menu-logo">
            <a href="/"><img src="{{ asset('imgs/logo.png') }}" alt="Logo" width="50"></a>
        </h5>
    </div>
    <nav>
        <div class="nav flex-column">
            <li id="dashboard" class="nav-item">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle nav-link" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Dashboard
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/dashboard">Insights</a></li>
                        <li><a class="dropdown-item" href="/dashboard/sales">Sales Insights</a></li>
                        <li><a class="dropdown-item" href="/dashboard/customer-insights">Customer Behavior Insights</a>
                        </li>
                        <li><a class="dropdown-item" href="/dashboard/financial-insights">Payment and Financial
                                Insights</a></li>
                        <li><a class="dropdown-item" href="/dashboard/order-fulfillment">Order Fulfillment Insights</a>
                        </li>
                        <li><a class="dropdown-item" href="/dashboard/custom-reports">Custom Reports</a></li>
                    </ul>
                </div>
            </li>
            <li id="collections" class="nav-item">
                <a class="nav-link" href="/dashboard/collections">Collections</a>
            </li>
            <li id="categories" class="nav-item">
                <a class="nav-link" href="/dashboard/categories">Categories</a>
            </li>
            <li id="sub-categories" class="nav-item">
                <a class="nav-link" href="/dashboard/sub-categories">Sub Categories</a>
            </li>
            <li id="events" class="nav-item">
                <a class="nav-link" href="/dashboard/events">Events</a>
            </li>
            <li id="currencies" class="nav-item">
                <a class="nav-link" href="/dashboard/currencies">Currencies</a>
            </li>
            <li id="languages" class="nav-item">
                <a class="nav-link" href="/dashboard/languages">Languages</a>
            </li>
            <li id="orders" class="nav-item">
                <a class="nav-link" href="/dashboard/orders">Orders</a>
            </li>
            <li id="artworks" class="nav-item">
                <a class="nav-link" href="/dashboard/artworks">Art List</a>
            </li>
            <li id="sellers" class="nav-item">
                <a class="nav-link" href="/dashboard/sellers">Seller list</a>
            </li>
            <li id="buyers" class="nav-item">
                <a class="nav-link" href="/dashboard/buyers">Buyer list</a>
            </li>
            <li id="admins" class="nav-item">
                <a class="nav-link" href="/dashboard/admins">Admins</a>
            </li>
        </div>
    </nav>
</aside>

<!-- mobile menu -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidemenu" aria-labelledby="sidemenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidemenuLabel">
            <a href="/"><img src="{{ asset('imgs/logo.png') }}" alt="Logo" width="50"></a>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="nav flex-column">
            <li class="nav-item">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle nav-link" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Dashboard
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/dashboard">Insights</a></li>
                        <li><a class="dropdown-item" href="/dashboard/sales">Sales Insights</a></li>
                        <li><a class="dropdown-item" href="/dashboard/customer-insights">Customer Behavior Insights</a>
                        </li>
                        <li><a class="dropdown-item" href="/dashboard/financial-insights">Payment and Financial
                                Insights</a></li>
                        <li><a class="dropdown-item" href="/dashboard/order-fulfillment">Order Fulfillment Insights</a>
                        </li>
                        <li><a class="dropdown-item" href="/dashboard/custom-reports">Custom Reports</a></li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/dashboard/collections">Collections</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/dashboard/categories">Categories</a>
            </li>
            <li" class="nav-item">
                <a class="nav-link" href="/dashboard/sub-categories">Sub Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard/events">Events</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard/currencies">Currencies</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard/languages">Languages</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard/orders">Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard/artworks">Art List</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard/sellers">Seller list</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard/buyers">Buyer list</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard/admins">Admins</a>
                </li>
        </div>
    </div>
</div>