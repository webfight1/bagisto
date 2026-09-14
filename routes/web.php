<?php

use App\Http\Controllers\Api\ImageResizeController;

Route::get('/storage/{path}', [ImageResizeController::class, 'resize'])
    ->where('path', '.*')
    ->middleware('web');
// Esto sandbox posts notifications to /esto/callback (Webkul package default).
// Route it to the working custom Api handler.
Route::post('/esto/callback', [\App\Http\Controllers\Api\EstoWebhookController::class, 'handle'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class, \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name("esto.callback");

// SEO sitemap + robots — Apache exceptions ->  Laravel serves these directly.
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt',  [\App\Http\Controllers\SitemapController::class, 'robots'])->name('robots');
