<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAdminPrivilege
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $routeToPrivilege = [
            'dashboard.index' => 'dashboard',
            'dashboard.custom-reports' => 'dashboard',
            'dashboard.order-fulfillment' => 'dashboard',
            'dashboard.financial-insights' => 'dashboard',
            'dashboard.customer-insights' => 'dashboard',
            'dashboard.sales' => 'dashboard',
            'dashboard.collections.index' => 'collections',
            'dashboard.categories.index' => 'categories',
            'dashboard.subcategories.index' => 'subcategories',
            'dashboard.events.index' => 'events',
            'dashboard.currencies.index' => 'currencies',
            'dashboard.languages.index' => 'languages',
            'dashboard.languages.language' => 'languages',
            'dashboard.orders.index' => 'orders',
            'dashboard.artworks.index' => 'artworks',
            'dashboard.sellers.index' => 'sellers',
            'dashboard.buyers.index' => 'buyers',
            'dashboard.admins.index' => 'admins',
        ];

        $currentRoute = $request->route()->getName();
        $adminPrivileges = json_decode($user->adminPrivileges->privileges ?? '[]');

        if (isset($routeToPrivilege[$currentRoute]) && !in_array($routeToPrivilege[$currentRoute], $adminPrivileges)) {
            return redirect()->back()->with('error', 'You are not authorized to access this section.');
        }

        return $next($request);
    }
}