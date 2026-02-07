<?php

use Illuminate\Support\Facades\Route;
use Webkul\Esto\Http\Controllers\EstoController;

Route::post('esto/callback', [EstoController::class, 'callback'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->name('esto.callback');
