<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleCalendarAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (request()->has('code')) {
        return app(GoogleCalendarAuthController::class)->callback();
    }
    return app(DashboardController::class)->index();
});
Route::get('/google/auth', [GoogleCalendarAuthController::class, 'redirect']);
