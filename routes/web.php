<?php

use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// API routes for items
Route::apiResource('api/items', ItemController::class);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
