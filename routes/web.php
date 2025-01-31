<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\Dashboard\LoginController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\CollectionController;


Route::get('login/{provider}/redirect', [SocialLoginController::class, 'redirectToProvider']);
Route::get('login/{provider}/callback', [SocialLoginController::class, 'handleProviderCallback']);



// Authentication Routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('admin.signin');
    Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');
});

// Dashboard Routes (Only accessible when authenticated)
Route::middleware(['auth', 'can:is-admin'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/sales', [DashboardController::class, 'sales'])->name('sales');
    Route::get('/customer-insights', [DashboardController::class, 'customer'])->name('customer-insights');
    Route::get('/financial-insights', [DashboardController::class, 'financial'])->name('financial-insights');
    Route::get('/order-fulfillment', [DashboardController::class, 'fulfillment'])->name('order-fulfillment');

    Route::get('/custom-reports', [DashboardController::class, 'reports'])->name('custom-reports');
    Route::post('/custom-reports/generate', [DashboardController::class, 'generateReport'])->name('custom-reports.generate');

    Route::name('collections.')->prefix('collections')->group(function () {
        Route::get('/', [CollectionController::class, 'index'])->name('index');
        Route::post('/', [CollectionController::class, 'store'])->name('store');
        Route::get('/{id}', [CollectionController::class, 'show'])->name('show');
        Route::put('/{id}', [CollectionController::class, 'update'])->name('update');
        Route::delete('/{id}', [CollectionController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [CollectionController::class, 'bulkDelete'])->name('bulk-delete');
    });
});
