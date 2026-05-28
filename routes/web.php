<?php

use App\Http\Controllers\Auth\AudioTokenLoginController;
use App\Http\Controllers\Auth\CourseTokenLoginController;
use App\Http\Controllers\Auth\OnlineCourseTokenLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/audio-token-login', AudioTokenLoginController::class)
    ->name('auth.audio-token-login');

Route::get('/auth/course-token-login', CourseTokenLoginController::class)
    ->name('auth.course-token-login');

Route::get('/auth/online-course-token-login', OnlineCourseTokenLoginController::class)
    ->name('auth.online-course-token-login');
