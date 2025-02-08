<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckAdminPrivilege;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\Dashboard\LoginController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\CollectionController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\TagController;
use App\Http\Controllers\Dashboard\EventController;
use App\Http\Controllers\Dashboard\CurrencyController;
use App\Http\Controllers\Dashboard\OrderController;
use App\Http\Controllers\Dashboard\LanguageController;
use App\Http\Controllers\Dashboard\ArtworkController;
use App\Http\Controllers\Dashboard\SellerController;
use App\Http\Controllers\Dashboard\BuyerController;
use App\Http\Controllers\Dashboard\AdminController;
use App\Http\Controllers\UserController;


Route::get('login/{provider}/redirect', [SocialLoginController::class, 'redirectToProvider']);
Route::get('login/{provider}/callback', [SocialLoginController::class, 'handleProviderCallback']);

// Authentication Routes
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::prefix('admin')->group(function () {
    Route::post('/login', [LoginController::class, 'login'])->name('admin.signin');
    Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');
});

// Dashboard Routes (Only accessible when authenticated)
Route::middleware(['auth', 'can:is-admin', CheckAdminPrivilege::class])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::post('/change-language', [UserController::class, 'updateLocale'])->name('change.language');

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
        Route::post('/{id}/toggle-active', [CollectionController::class, 'toggleActive'])->name('toggleActive');
        Route::post('/bulk-delete', [CollectionController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/{id}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('toggle-status');

    });

    Route::name('categories.')->prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{id}', [CategoryController::class, 'show'])->name('show');
        Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [CategoryController::class, 'bulkDelete'])->name('bulk-delete');
    });

    Route::name('tags.')->prefix('sub-categories')->group(function () {
        Route::get('/', [TagController::class, 'index'])->name('index');
        Route::post('/', [TagController::class, 'store'])->name('store');
        Route::get('/{id}', [TagController::class, 'show'])->name('show');
        Route::put('/{id}', [TagController::class, 'update'])->name('update');
        Route::delete('/{id}', [TagController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [TagController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-publish', [TagController::class, 'bulkPublish'])->name('bulk-publish');
        Route::post('/bulk-unpublish', [TagController::class, 'bulkUnpublish'])->name('bulk-unpublish');
        Route::post('/{id}/toggle-status', [TagController::class, 'toggleStatus'])
            ->name('toggle-status');

    });

    Route::name('events.')->prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::post('/', [EventController::class, 'store'])->name('store');
        Route::get('/{id}', [EventController::class, 'show'])->name('show');
        Route::put('/{id}', [EventController::class, 'update'])->name('update');
        Route::delete('/{id}', [EventController::class, 'destroy'])->name('destroy');
        // Bulk Actions
        Route::post('/bulk-delete', [EventController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-publish', [EventController::class, 'bulkPublish'])->name('bulk-publish');
        Route::post('/bulk-unpublish', [EventController::class, 'bulkUnpublish'])->name('bulk-unpublish');
    });

    Route::name('currencies.')->prefix('currencies')->group(function () {
        Route::get('/', [CurrencyController::class, 'index'])->name('index');
        Route::post('/', [CurrencyController::class, 'store'])->name('store');
        Route::get('/{id}', [CurrencyController::class, 'show'])->name('show');
        Route::put('/{id}', [CurrencyController::class, 'update'])->name('update');
        Route::delete('/{id}', [CurrencyController::class, 'destroy'])->name('destroy');

        // Bulk Actions
        Route::post('/bulk-delete', [CurrencyController::class, 'bulkDelete'])->name('bulk-delete');
    });

    Route::name('orders.')->prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::put('/{id}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{id}', [OrderController::class, 'destroy'])->name('destroy');

        // Bulk Actions
        Route::post('/bulk-delete', [OrderController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-update-status', [OrderController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
    });

    Route::name('languages.')->prefix('languages')->group(function () {
        Route::get('/', [LanguageController::class, 'index'])->name('index');
        Route::post('/', [LanguageController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [LanguageController::class, 'edit'])->name('edit');
        Route::put('/{id}', [LanguageController::class, 'update'])->name('update');
        Route::delete('/{id}', [LanguageController::class, 'destroy'])->name('destroy');

        Route::get('get/{language_id}', [LanguageController::class, 'getLanguage'])->name('show');
        Route::post('update/{language_id}', [LanguageController::class, 'updateLanguage'])->name('updateLanguage');
    });

    Route::name('artworks.')->prefix('artworks')->group(function () {
        Route::get('/', [ArtworkController::class, 'index'])->name('index');
        Route::post('/', [ArtworkController::class, 'store'])->name('store');
        Route::get('/{id}', [ArtworkController::class, 'show'])->name('show');
        Route::put('/{id}', [ArtworkController::class, 'update'])->name('update');
        Route::delete('/{id}', [ArtworkController::class, 'destroy'])->name('destroy');

        // Bulk Actions
        Route::post('/bulk-delete', [ArtworkController::class, 'bulkDelete'])->name('bulk-delete');
    });

    Route::name('sellers.')->prefix('sellers')->group(function () {
        Route::get('/', [SellerController::class, 'index'])->name('index');
        Route::post('/', [SellerController::class, 'store'])->name('store');
        Route::get('/{id}', [SellerController::class, 'show'])->name('show');
        Route::put('/{id}', [SellerController::class, 'update'])->name('update');
        Route::delete('/{id}', [SellerController::class, 'destroy'])->name('destroy');

        // Bulk Actions
        Route::post('/bulk-delete', [SellerController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-update-status', [SellerController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
        Route::post('/{id}/toggle-status', [SellerController::class, 'toggleStatus'])->name('toggle-status');
    });

    Route::name('buyers.')->prefix('buyers')->group(function () {
        Route::get('/', [BuyerController::class, 'index'])->name('index');
        Route::post('/', [BuyerController::class, 'store'])->name('store');
        Route::get('/{id}', [BuyerController::class, 'show'])->name('show');
        Route::put('/{id}', [BuyerController::class, 'update'])->name('update');
        Route::delete('/{id}', [BuyerController::class, 'destroy'])->name('destroy');

        // Bulk Actions
        Route::post('/bulk-delete', [BuyerController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-update-profile', [BuyerController::class, 'bulkUpdateProfile'])->name('bulk-update-profile');
    });

    Route::name('admins.')->prefix('admins')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::post('/', [AdminController::class, 'store'])->name('store');
        Route::get('/{id}', [AdminController::class, 'show'])->name('show');
        Route::put('/{id}', [AdminController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminController::class, 'remove'])->name('remove');
        Route::put('/{id}/update-privileges', [AdminController::class, 'updatePrivileges'])->name('update-privileges');
    });
});
