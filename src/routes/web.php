<?php

use Aghfatehi\Tap\Controllers\ChargeController;
use Aghfatehi\Tap\Controllers\OrderController;
use Aghfatehi\Tap\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

$prefix = config('tap.routes.prefix', 'tap');
$middleware = config('tap.routes.middleware', ['web']);

Route::middleware($middleware)->prefix($prefix)->group(function () {
    // ─── Phase 1: Charges ────────────────────────
    Route::post('/pay', [ChargeController::class, 'pay'])->name('tap.pay');
    Route::any('/callback', [ChargeController::class, 'callback'])->name('tap.callback');
    Route::get('/cancel', [ChargeController::class, 'cancel'])->name('tap.cancel');
    Route::get('/failure', [ChargeController::class, 'failure'])->name('tap.failure');

    // ─── Phase 1+2: Webhook ──────────────────────
    Route::post('/webhook', WebhookController::class)->name('tap.webhook');

    // ─── Phase 2: Order Management ───────────────
    Route::post('/authorize', [OrderController::class, 'authorize'])->name('tap.authorize');
    Route::post('/charge/capture', [OrderController::class, 'capture'])->name('tap.capture');
    Route::post('/charge/void', [OrderController::class, 'void'])->name('tap.void');
    Route::post('/charge/refund', [OrderController::class, 'refund'])->name('tap.refund');
    Route::get('/charge/{id}', [OrderController::class, 'details'])->name('tap.details');
});
