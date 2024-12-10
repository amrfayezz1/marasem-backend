<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SocialLoginController;


Route::get('login/{provider}/redirect', [SocialLoginController::class, 'redirectToProvider']);
Route::get('login/{provider}/callback', [SocialLoginController::class, 'handleProviderCallback']);