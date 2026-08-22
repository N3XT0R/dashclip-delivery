<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\VideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->name('api.v1.')->middleware('auth:api')->group(function (): void {
    Route::middleware('scope:videos:read')->group(function (): void {
        Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
        Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
    });
});
