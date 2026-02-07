<?php

use Illuminate\Support\Facades\Route;
use Webkul\Esto\Http\Controllers\EstoController;
use Webkul\Esto\Http\Controllers\EstoWebhookController;

Route::post('esto/callback', [EstoController::class, 'callback'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->name('esto.callback');

Route::post('api/payments/esto/webhook', [EstoWebhookController::class, 'handle'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->name('esto.webhook');
