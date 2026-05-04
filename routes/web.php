<?php

use App\Http\Controllers\Auth\AudioTokenLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/audio-token-login', AudioTokenLoginController::class)
    ->name('auth.audio-token-login');
