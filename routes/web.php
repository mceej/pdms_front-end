<?php

use App\Http\Controllers\PayoutDashboardController;
use App\Http\Controllers\ServedListController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/blank', function () {
    return view('blank');
});

Route::get('/api/payout-dashboard', PayoutDashboardController::class);
Route::post('/api/served-lists', [ServedListController::class, 'store']);
