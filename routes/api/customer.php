<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\Customer\CustomerOrderController;
use App\Http\Controllers\Api\Customer\PaymentController;
use App\Http\Controllers\Api\Customer\PaymentWebhookController;
use App\Http\Controllers\Api\CustomerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Controllers\Api\PaymentMethodController;

Route::prefix('customer')->group(function () {


    // Categories
    Route::get(
        '/categories',
        [CategoryController::class,'index']
    );


    // Menu Items
    Route::get(
        '/menu',
        [MenuItemController::class,'index']
    );
    Route::get(
        '/menu-items/{id}',
        [MenuItemController::class, 'show']
    );


    // Create Order
    Route::post(
        '/orders',
        [ApiOrderController::class,'store']
    );


    // Order Status
    Route::get(
        '/orders/{order}',
        [ApiOrderController::class,'show']
    );
    Route::apiResource('payment-methods', PaymentMethodController::class);
    Route::get(
        '/lookup/{phone}',
        [CustomerController::class,'find']
    );

    Route::post(
        '/orders',
        [CustomerOrderController::class,'store']);
    
    Route::post(
        '/payments/skipcash',
        [
            PaymentController::class,
            'skipcash'
        ]
    );

    Route::get(
        '/orders/{id}',
        [CustomerOrderController::class, 'show']
    );

    Route::post(
    '/payments/skipcash/retry/{order}',
        [PaymentController::class, 'retrySkipcash']
    );
});

