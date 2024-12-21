<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ArtworkController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PaymobController;


// auth
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/send-otp', [LoginController::class, 'sendOtp']);
Route::post('/reset-password', [LoginController::class, 'resetPassword']);

Route::get('/artworks', [ArtworkController::class, 'fetchArtworks']);
Route::get('/search', [FilterController::class, 'search']);
Route::get('/filters', [FilterController::class, 'getFilters']);
Route::post('/filters/apply', [FilterController::class, 'applyFilters']);

Route::get('/collections', [CollectionController::class, 'index']);
Route::get('/collections/{id}', [CollectionController::class, 'show']);

Route::get('/events', [EventController::class, 'getEvents']);

// group with sanctum
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/add-address', [AddressController::class, 'store']);
    Route::post('/change-currency', [UserController::class, 'changeCurrency']);

    Route::post('/logout', [LoginController::class, 'logout']);

    // like
    Route::post('artworks/{id}/like', [ArtworkController::class, 'like']);
    Route::delete('artworks/{id}/like', [ArtworkController::class, 'unlike']);

    Route::post('/cart', [CartController::class, 'addToCart']);
    Route::delete('/cart', [CartController::class, 'removeFromCart']);
    Route::get('/cart', [CartController::class, 'getCartItems']);
    Route::get('/checkout', [CartController::class, 'getCheckoutData']);
    Route::post('/order', [OrderController::class, 'placeOrder']);
    Route::post('/custom-order', [OrderController::class, 'placeCustomOrder']);
    Route::get('/orders', [OrderController::class, 'viewOrders']);

    Route::post('/collections/{id}/follow', [CollectionController::class, 'follow']);
    Route::post('/collections/{id}/unfollow', [CollectionController::class, 'unfollow']);
});

Route::middleware(['auth:sanctum', 'can:is-artist'])->group(function () {
    // adding artwork
    Route::get('/collections-tags', [ArtworkController::class, 'getCollectionsAndTags']);
    Route::post('/artworks', [ArtworkController::class, 'createArtwork']);
    // registration as artist
    Route::post('/add-social-media-links', [RegisterController::class, 'addSocialMediaLinks']);
    Route::get('/get-categories', [RegisterController::class, 'getCategories']);
    Route::post('/choose-categories', [RegisterController::class, 'addCategories']);
    Route::post('/add-pickup-location', [RegisterController::class, 'addPickupLocation']);

    Route::get('/artist/customized-orders', [OrderController::class, 'showCustomizedForArtist']);
    Route::get('/artist/orders', [OrderController::class, 'viewOrdersForArtist']);
});

// payments
Route::post('/paymob/processed-callback', [PaymobController::class, 'processedCallback']);
Route::get('/paymob/response-callback', [PaymobController::class, 'responseCallback']);

Route::get('/payment/success', function () {
    $successMessage = session('success', 'Payment completed successfully!');
    return view('payment.success', compact('successMessage'));
})->name('payment.success');

Route::get('/payment/error', function () {
    $errorMessage = session('error', 'Payment failed. Please try again.');
    return view('payment.error', compact('errorMessage'));
})->name('payment.error');