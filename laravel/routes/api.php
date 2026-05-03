<?php

/**
 * Part A JSON API routes; served under the `/api` prefix (see `bootstrap/app.php`).
 */

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\PropertyController;
use Illuminate\Support\Facades\Route;

Route::prefix('projects')->group(function (): void {
    Route::get('/', [ProjectController::class, 'index']);
});

Route::prefix('properties')->group(function (): void {
    Route::get('/', [PropertyController::class, 'index']);
    Route::post('/', [PropertyController::class, 'store']);
    Route::get('/{property}', [PropertyController::class, 'show']);
    Route::match(['put', 'patch'], '/{property}', [PropertyController::class, 'update']);
    Route::delete('/{property}', [PropertyController::class, 'destroy']);
});
