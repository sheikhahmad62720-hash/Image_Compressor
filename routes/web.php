<?php

use App\Http\Controllers\ImageCompressionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('ImageCompressor');
});

Route::post('/compress', [ImageCompressionController::class, 'compress']);
