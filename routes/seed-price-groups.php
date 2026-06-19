<?php

use App\Http\Controllers\Admin\SeedPriceGroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('catalog/seed-price-groups')->group(function () {
    Route::get('', [SeedPriceGroupController::class, 'index'])
        ->name('admin.catalog.seed_price_groups.index');

    Route::put('', [SeedPriceGroupController::class, 'update'])
        ->name('admin.catalog.seed_price_groups.update');
});
