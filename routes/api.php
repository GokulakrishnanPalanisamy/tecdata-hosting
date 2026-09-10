<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id');

    // initiate the payment for the order
    Route::post('/orders/{id}/payment', [PaymentController::class, 'initiatePayment'])->whereNumber('id');

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/payments', [AdminController::class, 'payments']);
        Route::get('/webhook-events', [AdminController::class, 'webhookEvents']);
    });
});

Route::prefix('webhooks')->middleware('verify.webhook.signature')->group(function () {
    Route::post('/payment', [WebhookController::class, 'payment']);
});
