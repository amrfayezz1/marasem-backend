<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/send-otp', [LoginController::class, 'sendOtp']);
Route::post('/reset-password', [LoginController::class, 'resetPassword']);

// group with sanctum
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/add-social-media-links', [RegisterController::class, 'addSocialMediaLinks']);
    Route::post('/choose-categories', [RegisterController::class, 'addCategories']);
    Route::post('/add-pickup-location', [RegisterController::class, 'addPickupLocation']);

    Route::post('/logout', [LoginController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});