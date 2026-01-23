<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Route patterns to ensure numeric IDs
Route::pattern('customer', '[0-9]+');
Route::pattern('product', '[0-9]+');

// Public endpoints
Route::get('/products', [ProductController::class, 'index']);
Route::post('/customers', [CustomerController::class, 'store']);

// Subscription routes
Route::middleware('api.key')->group(function () {
    Route::post('/customers/{customer}/subscriptions', [SubscriptionController::class, 'store']);
    Route::get('/customers/{customer}/subscriptions', [SubscriptionController::class, 'index']);
    Route::delete('/customers/{customer}/subscriptions/{product}', [SubscriptionController::class, 'destroy']);
});
