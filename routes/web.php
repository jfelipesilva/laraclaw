<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleCalendarAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index']);
Route::get('/google/auth', [GoogleCalendarAuthController::class, 'redirect']);
Route::get('/google/callback', [GoogleCalendarAuthController::class, 'callback']);
