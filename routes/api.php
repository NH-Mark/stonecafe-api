<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\Customer\PaymentController;
use App\Http\Controllers\Api\DiningSessionController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\FoodSymbolController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\MenuItemTagController;
use App\Http\Controllers\Api\ModifierController;
use App\Http\Controllers\Api\ModifierGroupController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderTypeController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PrintJobController;
use App\Http\Controllers\Api\SalesDashboardController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\OrderSourceController;
use App\Http\Controllers\Api\RestaurantTableController;
use App\Http\Controllers\Api\TablePaymentController;
use App\Http\Controllers\MenuItemModifierGroupController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/api/customer.php';

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [
    AuthController::class,
    'resetPassword'
]);

Route::get(
    '/print/jobs',
    [PrintJobController::class,'pending']
);


Route::post(
    '/print/jobs/{id}/done',
    [PrintJobController::class,'done']
);

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
        '/menu-categories',
        [CategoryController::class, 'index']
    );

    Route::get(
        '/menu-items-list',
        [MenuItemController::class,'listMenu']);

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
    Route::apiResource('discounts', DiscountController::class);
    Route::apiResource('payment-methods', PaymentMethodController::class);
    Route::apiResource('order-sources', OrderSourceController::class);
    Route::get(
        '/list-discounts',
        [DiscountController::class,'listDiscounts']
    );
    Route::get(
        '/list-payment-methods',
        [PaymentMethodController::class,'listPaymentMethods']
    );
     Route::get(
        '/list-order-sources',
        [OrderSourceController::class,'listOrderSources']
    );
    Route::patch(
        '/orders/{order}/payment-status',
        [OrderController::class, 'updatePaymentStatus']
    );
    Route::post(
        '/orders/{order}/payments',
        [OrderController::class, 'storePayment']
    );
     Route::post(
        '/orders',
        [OrderController::class, 'store']
    );
    Route::get(
        '/kitchen/orders',
        [KitchenController::class,'index']
    );
    Route::patch(
        '/kitchen/orders/{order}/status',
        [KitchenController::class,'updateStatus']
    );
    Route::apiResource('/pos/tables', RestaurantTableController::class);

    Route::post(
        '/pos/dining-sessions',
        [DiningSessionController::class, 'store']
    );

    Route::get(
        '/pos/dining-sessions/{diningSession}',
        [DiningSessionController::class, 'show']
    );
    Route::post(
        '/orders/{order}/items',
        [OrderController::class, 'addItems']
    );
    Route::post(
        '/table-payments',
        [TablePaymentController::class, 'store']
    );

    Route::post(
        '/orders/{order}/order-status',
        [OrderController::class, 'updateOrderStatus']
    );
    Route::patch(
        '/pos/dining-sessions/{diningSession}/transfer-table',
        [DiningSessionController::class, 'transferTable']
    );
  

});