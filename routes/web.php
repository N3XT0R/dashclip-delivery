<?php

use App\Http\Controllers\AssignmentDownloadController;
use App\Http\Controllers\ChannelApprovalController;
use App\Http\Controllers\DropboxController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\TokenApprovalController;
use App\Http\Controllers\ZipController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/game', function () {
    return view('game');
})->name('game');

Route::get('/changelog', function () {
    return Str::markdown(file_get_contents(base_path('CHANGELOG.md')));
})->name('changelog');

Route::view('/impressum', 'impressum')->name('impressum');
Route::view('/tos', 'tos')->name('tos');

Route::view('/datenschutz', 'datenschutz')->name('datenschutz');
Route::get('/license', function () {
    return nl2br(file_get_contents(base_path('LICENSE')));
})->name('license');

Route::get('/offer/{batch}/{channel}', [OfferController::class, 'show'])->name('offer.show');
// ZIP-Download via asynchronen Job
Route::get('/offer/{batch}/{channel}/unused', [OfferController::class, 'showUnused'])->name('offer.unused.show');
Route::post('/offer/{batch}/{channel}/unused', [OfferController::class, 'storeUnused'])->name('offer.unused.store');

Route::get('/d/{assignment}', [AssignmentDownloadController::class, 'download'])->name('assignments.download');


Route::get('/dropbox/connect', [DropboxController::class, 'connect'])->name('dropbox.connect');
Route::get('/dropbox/callback', [DropboxController::class, 'callback'])->name('dropbox.callback');
Route::post('/zips/channel/{channel}', [ZipController::class, 'startForChannel'])->name('zips.channel.start');
/**
 * @deprecated Use /zips/channel/{channel} instead
 */
Route::post('/zips/{batch}/{channel}', [ZipController::class, 'start'])->name('zips.start');
Route::get('/zips/{id}/progress', [ZipController::class, 'progress'])->name('zips.progress');
Route::get('/zips/{id}/download', [ZipController::class, 'download'])->name('zips.download');

Route::get('/channels/{channel}/approve/{token}', [ChannelApprovalController::class, 'approve'])
    ->name('channels.approve');

Route::post('/action-token/approve/{actionToken}', [TokenApprovalController::class, 'update'])
    ->name('action-tokens.approve-channel');
