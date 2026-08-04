<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Customer\PaymentController;
use App\Http\Controllers\Api\Customer\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get(
    '/orders/skipcash/callback/{order}',
    [PaymentController::class, 'skipcashCallback']
)->name('skipcash.callback');


Route::get('/test-skipcash', function () {
    return "working";
});
Route::post('/orders/skipcash/webhook/{order}', [
    PaymentWebhookController::class,
    'handle'
]);

