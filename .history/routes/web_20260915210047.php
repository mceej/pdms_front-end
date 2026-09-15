<?php

use App\Http\Controllers\PayoutDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/blank', function () {
    return view('blank');
});

Route::get('/api/payout-dashboard', PayoutDashboardController::class);
