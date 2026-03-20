<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleCalendarAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index']);
Route::get('/google/auth', [GoogleCalendarAuthController::class, 'redirect']);
Route::get('/google/callback', [GoogleCalendarAuthController::class, 'callback']);
Route::get('/api/google/calendars', [GoogleCalendarAuthController::class, 'listCalendars']);
Route::post('/api/google/calendars', [GoogleCalendarAuthController::class, 'saveCalendars']);
