<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->group(function () {
    Route::post('/initiate', [PaymentController::class, 'initiate'])
        ->middleware('throttle:6,1')
        ->name('api.payments.initiate');

    Route::get('/{reference}/status', [PaymentController::class, 'status'])
        ->middleware('throttle:30,1')
        ->name('api.payments.status');

    Route::post('/callback/{gateway}', [PaymentController::class, 'callback'])
        ->middleware('throttle:60,1')
        ->name('api.payments.callback');
});
