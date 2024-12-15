<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ArtworkController;
use App\Http\Controllers\AddressController;

// auth
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/send-otp', [LoginController::class, 'sendOtp']);
Route::post('/reset-password', [LoginController::class, 'resetPassword']);

// group with sanctum
Route::group(['middleware' => 'auth:sanctum'], function () {
    // registration as artist
    Route::post('/add-social-media-links', [RegisterController::class, 'addSocialMediaLinks']);
    Route::post('/choose-categories', [RegisterController::class, 'addCategories']);
    Route::post('/add-pickup-location', [RegisterController::class, 'addPickupLocation']);
    Route::post('/add-address', [AddressController::class, 'store']);

    Route::post('/logout', [LoginController::class, 'logout']);

    // like
    Route::post('artworks/{id}/like', [ArtworkController::class, 'like']);
    Route::delete('artworks/{id}/like', [ArtworkController::class, 'unlike']);
});

Route::get('/artworks', [ArtworkController::class, 'fetchArtworks']);

Route::middleware(['auth:sanctum', 'can:is-artist'])->group(function () {
    // adding artwork
    Route::get('/collections-tags', [ArtworkController::class, 'getCollectionsAndTags']);
    Route::post('/artworks', [ArtworkController::class, 'createArtwork']);
});