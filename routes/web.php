<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RestaurantController;
use Illuminate\Support\Facades\Route;

// Restaurant Demo Pages
Route::get('/', [RestaurantController::class, 'index'])->name('restaurant.index');
Route::get('/menu', function () {
    return redirect()->route('restaurant.index');
});

// Checkout & Order Placement (triggers Server Analytics SDK)
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');

// Analytics Dashboard UI
Route::get('/analytics', [DashboardController::class, 'index'])->name('analytics.dashboard');
Route::get('/dashboard', function () {
    return redirect()->route('analytics.dashboard');
});
