<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\BoardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('board/{uuid}', [BoardController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('board');

// API routes for items
Route::apiResource('api/items', ItemController::class);

// API routes for boards (CRUD)
Route::middleware(['auth'])->group(function () {
    Route::get('api/boards/{uuid}/items', [BoardController::class, 'itemsByUuid']);
    Route::apiResource('api/boards', BoardController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
