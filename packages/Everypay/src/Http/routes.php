<?php

use Illuminate\Support\Facades\Route;
use Webkul\Everypay\Http\Controllers\EverypayController;

Route::post('everypay/callback', [EverypayController::class, 'callback'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->name('everypay.callback');
