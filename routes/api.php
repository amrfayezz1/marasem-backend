<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\SetLocale;

use App\Http\Controllers\AddressController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\ArtworkController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymobController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\UserController;

use App\Http\Controllers\TranslationController;


// auth
Route::middleware([SetLocale::class])->group(function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/send-otp', [LoginController::class, 'sendOtp']);
    Route::post('/reset-password', [LoginController::class, 'resetPassword']);

    Route::get('/artworks', [ArtworkController::class, 'fetchArtworks']);
    Route::get('/artworks/{id}/view', [ArtworkController::class, 'viewArtwork']);
    Route::get('/search', [FilterController::class, 'search']);
    Route::get('/filters', [FilterController::class, 'getFilters']);
    Route::post('/filters/apply', [FilterController::class, 'applyFilters']);

    Route::get('/collections', [CollectionController::class, 'index']);
    Route::get('/collections/{id}', [CollectionController::class, 'show']);

    Route::get('/events', [EventController::class, 'getEvents']);

    // group with sanctum
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('/add-address', [AddressController::class, 'store']);
        Route::put('/update-address/{id}', [AddressController::class, 'update']);
        Route::delete('/delete-address/{id}', [AddressController::class, 'destroy']);

        Route::post('/change-currency', [UserController::class, 'changeCurrency']);
        Route::post('/change-language', [UserController::class, 'updateLocale']);

        Route::post('/logout', [LoginController::class, 'logout']);

        // like
        Route::post('/artworks/{id}/like', [ArtworkController::class, 'like']);
        Route::delete('/artworks/{id}/like', [ArtworkController::class, 'unlike']);

        Route::post('/cart', [CartController::class, 'addToCart']);
        Route::delete('/cart', [CartController::class, 'removeFromCart']);
        Route::get('/cart', [CartController::class, 'getCartItems']);
        Route::get('/checkout', [CartController::class, 'getCheckoutData']);
        Route::post('/order', [OrderController::class, 'placeOrder']);
        Route::post('/custom-order', [OrderController::class, 'placeCustomOrder']);
        Route::get('/orders', [OrderController::class, 'viewOrders']);

        Route::post('/validate-promocode', [OrderController::class, 'validatePromoCode']);

        Route::post('/collections/{id}/follow', [CollectionController::class, 'follow']);
        Route::post('/collections/{id}/unfollow', [CollectionController::class, 'unfollow']);
        Route::post('/artists/{artist}/follow', [ArtistController::class, 'followArtist']);
        Route::post('/artists/{artist}/unfollow', [ArtistController::class, 'unfollowArtist']);

        Route::post('/user/profile-picture', [UserController::class, 'updateProfilePicture']);
        Route::get('/user/account', [UserController::class, 'getUserInfo']);
        Route::put('/user/account', [UserController::class, 'updateUserInfo']);

        Route::get('/user/my-orders', [UserController::class, 'viewOrders']);
        Route::get('/user/my-orders/{id}', [UserController::class, 'viewOrderDetails']);
        Route::get('/user/customized-orders/{id}', [UserController::class, 'viewCustomizedOrderDetails']);
    });

    Route::middleware(['auth:sanctum', 'can:is-artist'])->group(function () {
        Route::post('/user/cover-image', [ArtistController::class, 'updateCoverImage']);
        // adding artwork
        Route::get('/collections-tags', [ArtworkController::class, 'getCollectionsAndTags']);
        Route::post('/artworks', [ArtworkController::class, 'createArtwork']);
        Route::put('/artworks/{id}', [ArtworkController::class, 'updateArtwork']);
        Route::delete('/artworks/{id}', [ArtworkController::class, 'deleteArtwork']);

        Route::put('/artist/general-info', [ArtistController::class, 'updateGeneralInfo']);
        Route::put('/artist/about-me', [ArtistController::class, 'updateAboutMe']);
        Route::put('/artist/pickup-location', [ArtistController::class, 'updatePickupLocation']);
        Route::put('/artist/focus', [ArtistController::class, 'updateFocus']);
        Route::get('/artist/focus', [ArtistController::class, 'getFocus']);
        Route::get('/artist/get-balance', [ArtistController::class, 'getAvailableBalance']);

        Route::get('/artist/my-orders', [ArtistController::class, 'viewOrders']);
        Route::get('/artist/my-orders/{id}', [ArtistController::class, 'viewOrderDetails']);
        Route::get('/artist/customized-orders/{id}', [ArtistController::class, 'viewCustomizedOrderDetails']);

        // registration as artist
        Route::post('/add-social-media-links', [RegisterController::class, 'addSocialMediaLinks']);
        Route::get('/get-categories', [RegisterController::class, 'getCategories']);
        Route::post('/choose-categories', [RegisterController::class, 'addCategories']);
        Route::post('/add-pickup-location', [RegisterController::class, 'addPickupLocation']);

        Route::get('/artist/customized-orders', [OrderController::class, 'showCustomizedForArtist']);
        Route::get('/artist/orders', [OrderController::class, 'viewOrdersForArtist']);

        Route::post('/customized-order/{id}/respond', [ArtistController::class, 'respondToCustomizedOrder']);
        route::get('/artist/profile', [UserController::class, 'getArtistProfile']);
    });

    Route::middleware(['auth:sanctum', 'can:is-admin'])->prefix('/admin')->group(function () {
        Route::post('/languages', [TranslationController::class, 'addLanguage']);
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

    // artists
    Route::get('/artists', [ArtistController::class, 'getArtists']);
    Route::get('/artists/{id}', [ArtistController::class, 'getArtistData']);

    Route::post('/translations/static', [TranslationController::class, 'getStaticTranslation']);
    Route::post('/translations/static/batch', [TranslationController::class, 'getStaticTranslations']);
    Route::post('/set-locale', [TranslationController::class, 'setLocale']);

});