<?php

use App\Http\Controllers\Api\ImageResizeController;

Route::get('/storage/{path}', [ImageResizeController::class, 'resize'])->where('path', '.*');
