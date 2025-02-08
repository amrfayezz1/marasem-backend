@php
    $adminPrivileges = json_decode(auth()->user()->adminPrivileges->privileges) ?? [];
@endphp
<aside id="sidebarContent">
    <div class="d-flex justify-content-center align-items-center">
        <h5 class="mb-0 menu-logo">
            <a href="/dashboard"><img src="{{ asset('imgs/logo.png') }}" alt="Logo" width="50"></a>
        </h5>
    </div>
    <nav>
        <div class="nav flex-column">
            @if(in_array('dashboard', $adminPrivileges))
                <li id="dashboard" class="nav-item">
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle nav-link" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            {{ tt('Dashboard') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/dashboard">{{ tt('Insights') }}</a></li>
                            <li><a class="dropdown-item" href="/dashboard/sales">{{ tt('Sales Insights') }}</a></li>
                            <li><a class="dropdown-item"
                                    href="/dashboard/customer-insights">{{ tt('Customer Behavior Insights') }}</a>
                            </li>
                            <li><a class="dropdown-item" href="/dashboard/financial-insights">{{ tt('Payment and Financial
                                                    Insights')}}</a></li>
                            <li><a class="dropdown-item"
                                    href="/dashboard/order-fulfillment">{{ tt('Order Fulfillment Insights') }}</a>
                            </li>
                            <li><a class="dropdown-item" href="/dashboard/custom-reports">{{ tt('Custom Reports') }}</a>
                            </li>
                        </ul>
                    </div>
                </li>
            @endif
            @if(in_array('collections', $adminPrivileges))
                <li id="collections" class="nav-item">
                    <a class="nav-link" href="/dashboard/collections">{{ tt('Collections') }}</a>
                </li>
            @endif
            @if(in_array('categories', $adminPrivileges))
                <li id="categories" class="nav-item">
                    <a class="nav-link" href="/dashboard/categories">{{ tt('Categories') }}</a>
                </li>
            @endif
            @if(in_array('subcategories', $adminPrivileges))
                <li id="sub-categories" class="nav-item">
                    <a class="nav-link" href="/dashboard/sub-categories">{{ tt('Sub Categories') }}</a>
                </li>
            @endif
            @if(in_array('events', $adminPrivileges))
                <li id="events" class="nav-item">
                    <a class="nav-link" href="/dashboard/events">{{ tt('Events') }}</a>
                </li>
            @endif
            @if(in_array('currencies', $adminPrivileges))
                <li id="currencies" class="nav-item">
                    <a class="nav-link" href="/dashboard/currencies">{{ tt('Currencies') }}</a>
                </li>
            @endif
            @if(in_array('languages', $adminPrivileges))
                <li id="languages" class="nav-item">
                    <a class="nav-link" href="/dashboard/languages">{{ tt('Languages') }}</a>
                </li>
            @endif
            @if(in_array('orders', $adminPrivileges))
                <li id="orders" class="nav-item">
                    <a class="nav-link" href="/dashboard/orders">{{ tt('Orders') }}</a>
                </li>
            @endif
            @if(in_array('artworks', $adminPrivileges))
                <li id="artworks" class="nav-item">
                    <a class="nav-link" href="/dashboard/artworks">{{ tt('Art list') }}</a>
                </li>
            @endif
            @if(in_array('sellers', $adminPrivileges))
                <li id="sellers" class="nav-item">
                    <a class="nav-link" href="/dashboard/sellers">{{ tt('Seller list') }}</a>
                </li>
            @endif
            @if(in_array('buyers', $adminPrivileges))
                <li id="buyers" class="nav-item">
                    <a class="nav-link" href="/dashboard/buyers">{{ tt('Buyer list') }}</a>
                </li>
            @endif
            @if(in_array('admins', $adminPrivileges))
                <li id="admins" class="nav-item">
                    <a class="nav-link" href="/dashboard/admins">{{ tt('Admins') }}</a>
                </li>
            @endif
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
            @if(in_array('dashboard', $adminPrivileges))
                <li id="dashboard" class="nav-item">
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle nav-link" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            {{ tt('Dashboard') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/dashboard">{{ tt('Insights') }}</a></li>
                            <li><a class="dropdown-item" href="/dashboard/sales">{{ tt('Sales Insights') }}</a></li>
                            <li><a class="dropdown-item"
                                    href="/dashboard/customer-insights">{{ tt('Customer Behavior Insights') }}</a>
                            </li>
                            <li><a class="dropdown-item" href="/dashboard/financial-insights">{{ tt('Payment and Financial
                                                    Insights')}}</a></li>
                            <li><a class="dropdown-item"
                                    href="/dashboard/order-fulfillment">{{ tt('Order Fulfillment Insights') }}</a>
                            </li>
                            <li><a class="dropdown-item" href="/dashboard/custom-reports">{{ tt('Custom Reports') }}</a>
                            </li>
                        </ul>
                    </div>
                </li>
            @endif
            @if(in_array('collections', $adminPrivileges))
                <li id="collections" class="nav-item">
                    <a class="nav-link" href="/dashboard/collections">{{ tt('Collections') }}</a>
                </li>
            @endif
            @if(in_array('categories', $adminPrivileges))
                <li id="categories" class="nav-item">
                    <a class="nav-link" href="/dashboard/categories">{{ tt('Categories') }}</a>
                </li>
            @endif
            @if(in_array('subcategories', $adminPrivileges))
                <li id="sub-categories" class="nav-item">
                    <a class="nav-link" href="/dashboard/sub-categories">{{ tt('Sub Categories') }}</a>
                </li>
            @endif
            @if(in_array('events', $adminPrivileges))
                <li id="events" class="nav-item">
                    <a class="nav-link" href="/dashboard/events">{{ tt('Events') }}</a>
                </li>
            @endif
            @if(in_array('currencies', $adminPrivileges))
                <li id="currencies" class="nav-item">
                    <a class="nav-link" href="/dashboard/currencies">{{ tt('Currencies') }}</a>
                </li>
            @endif
            @if(in_array('languages', $adminPrivileges))
                <li id="languages" class="nav-item">
                    <a class="nav-link" href="/dashboard/languages">{{ tt('Languages') }}</a>
                </li>
            @endif
            @if(in_array('orders', $adminPrivileges))
                <li id="orders" class="nav-item">
                    <a class="nav-link" href="/dashboard/orders">{{ tt('Orders') }}</a>
                </li>
            @endif
            @if(in_array('artworks', $adminPrivileges))
                <li id="artworks" class="nav-item">
                    <a class="nav-link" href="/dashboard/artworks">{{ tt('Art list') }}</a>
                </li>
            @endif
            @if(in_array('sellers', $adminPrivileges))
                <li id="sellers" class="nav-item">
                    <a class="nav-link" href="/dashboard/sellers">{{ tt('Seller list') }}</a>
                </li>
            @endif
            @if(in_array('buyers', $adminPrivileges))
                <li id="buyers" class="nav-item">
                    <a class="nav-link" href="/dashboard/buyers">{{ tt('Buyer list') }}</a>
                </li>
            @endif
            @if(in_array('admins', $adminPrivileges))
                <li id="admins" class="nav-item">
                    <a class="nav-link" href="/dashboard/admins">{{ tt('Admins') }}</a>
                </li>
            @endif
        </div>
    </div>
</div>