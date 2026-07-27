<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FoodSymbolController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\MenuItemTagController;
use App\Http\Controllers\Api\ModifierController;
use App\Http\Controllers\Api\ModifierGroupController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderTypeController;
use App\Http\Controllers\Api\SalesDashboardController;
use App\Http\Controllers\MenuItemModifierGroupController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [
    AuthController::class,
    'resetPassword'
]);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [AuthController::class, 'user']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('roles', RoleController::class);

    Route::apiResource('users', UserController::class);
    
    Route::apiResource('permissions', PermissionController::class);

    Route::apiResource('locations', LocationController::class);

    Route::post(
        '/uploads/image',
        [UploadController::class, 'store']
    );

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('menu-items', MenuItemController::class);
    Route::get(
        '/menu-items/{id}',
        [MenuItemController::class, 'show']
    );

    Route::apiResource('modifier-groups', ModifierGroupController::class);
    Route::apiResource('modifiers', ModifierController::class);


    Route::apiResource('menu-item-tags', MenuItemTagController::class);
    Route::apiResource('food-symbols', FoodSymbolController::class);

    Route::put('/account/profile', [AccountController::class, 'updateProfile']);

    Route::put('/account/password', [AccountController::class, 'changePassword']);

    Route::prefix('sales')
        ->group(function () {
            Route::get(
                '/dashboard',
                [SalesDashboardController::class,'index']
            );

        });

    Route::apiResource('order-types', OrderTypeController::class);
    Route::apiResource('orders', OrderController::class);

});