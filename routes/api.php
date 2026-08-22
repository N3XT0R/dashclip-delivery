<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ChannelController;
use App\Http\Controllers\Api\V1\OfferController;
use App\Http\Controllers\Api\V1\VideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->name('api.v1.')->middleware('auth:api')->group(function (): void {
    Route::middleware('scope:channels:read')->group(function (): void {
        Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index');
        Route::get('/channels/{channel}', [ChannelController::class, 'show'])->name('channels.show')->whereNumber('channel');
    });

    Route::middleware('scope:channels:write')->group(function (): void {
        Route::patch('/channels/{channel}', [ChannelController::class, 'update'])
            ->name('channels.update')
            ->whereNumber('channel');
    });

    Route::middleware('scope:videos:read')->group(function (): void {
        Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
        Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show')->whereNumber('video');
    });

    Route::middleware('scope:videos:write')->group(function (): void {
        Route::post('/videos', [VideoController::class, 'store'])->name('videos.store');
        Route::patch('/videos/{video}', [VideoController::class, 'update'])
            ->name('videos.update')
            ->whereNumber('video');
    });
    Route::delete('/videos/{video}', [VideoController::class, 'destroy'])
        ->middleware('scope:videos:delete')
        ->name('videos.destroy')
        ->whereNumber('video');

    Route::middleware('scope:offers:read')->group(function (): void {
        Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');
        Route::get('/offers/{offer}', [OfferController::class, 'show'])->name('offers.show')->whereNumber('offer');
    });

    Route::middleware('scope:offers:write')->group(function (): void {
        Route::post('/offers', [OfferController::class, 'store'])->name('offers.store');
        Route::post('/offers/{offer}/comment', [OfferController::class, 'comment'])
            ->name('offers.comment')
            ->whereNumber('offer');
    });
});
