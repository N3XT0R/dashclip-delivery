<?php

declare(strict_types=1);

use App\Enum\Guard\GuardEnum;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\ApiDocsController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\PublicSitemapController;
use App\Http\Controllers\AssignmentDownloadController;
use App\Http\Controllers\DropboxController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\PublicDocumentController;
use App\Http\Controllers\PublicLocaleController;
use App\Http\Middleware\SetPublicLocale;
use App\Http\Controllers\TokenApprovalController;
use App\Http\Controllers\ZipController;
use Illuminate\Support\Facades\Route;

Route::middleware(SetPublicLocale::class)->group(function (): void {
    Route::get('/sitemap.xml', PublicSitemapController::class)->name('sitemap');
    Route::get('/robots.txt', [PublicSitemapController::class, 'robots'])->name('robots');
    Route::post('/language', PublicLocaleController::class)->name('public.locale');

    foreach (['de' => 'blog', 'en' => 'en/blog'] as $locale => $prefix) {
        Route::prefix($prefix)->name('blog.'.($locale === 'en' ? 'en.' : ''))->group(function () use ($locale): void {
            Route::get('/', [BlogController::class, 'index'])->name('index');
            Route::get('/search', [BlogController::class, 'index'])->name('search');
            Route::get('/feed.xml', [BlogController::class, 'feed'])->name('feed');
            Route::get('/'.($locale === 'de' ? 'kategorie' : 'category').'/{slug}', [BlogController::class, 'index'])->name('category');
            Route::get('/'.($locale === 'de' ? 'thema' : 'tag').'/{slug}', [BlogController::class, 'index'])->name('tag');
            Route::get('/{slug}', [BlogController::class, 'show'])->name('show');
        });
    }
    Route::get('/blog-preview/{translation}', [BlogController::class, 'preview'])->middleware('auth')->name('blog.preview');
    Route::get('/blog-sitemap.xml', [BlogController::class, 'sitemap'])->name('blog.sitemap');

    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::get('/game', function () {
        return view('game');
    })->name('game');

    Route::get('/changelog', [PublicDocumentController::class, 'changelog'])->name('changelog');

    Route::view('/impressum', 'impressum')->name('impressum');
    Route::view('/tos', 'tos')->name('tos');

    Route::view('/datenschutz', 'datenschutz')->name('datenschutz');
    Route::get('/license', [PublicDocumentController::class, 'license'])->name('license');

    Route::get('/api-docs', [ApiDocsController::class, 'index'])->name('api-docs');

    Route::get('/offer/{batch}/{channel}', [OfferController::class, 'show'])->name('offer.show');
    // ZIP-Download via asynchronen Job
    Route::get('/offer/{batch}/{channel}/unused', [OfferController::class, 'showUnused'])->name('offer.unused.show');
    Route::post('/offer/{batch}/{channel}/unused', [OfferController::class, 'storeUnused'])->name('offer.unused.store');

    /**
     * @deprecated Use /zips/channel/{channel} instead
     */
    Route::get('/d/{assignment}', [AssignmentDownloadController::class, 'download'])->name('assignments.download');


    Route::get('/dropbox/connect', [DropboxController::class, 'connect'])->name('dropbox.connect');
    Route::get('/dropbox/callback', [DropboxController::class, 'callback'])->name('dropbox.callback');
    Route::get('/offers/{assignment}/download', [ZipController::class, 'video'])->middleware('signed')->name('offers.video.download');
    Route::post('/zips/channel/{channel}', [ZipController::class, 'startForChannel'])->middleware('signed')->name('zips.channel.start');
    /**
     * @deprecated Use /zips/channel/{channel} instead
     */
    Route::post('/zips/{batch}/{channel}', [ZipController::class, 'start'])->middleware('signed')->name('zips.start');
    Route::get('/zips/{id}/progress', [ZipController::class, 'progress'])->middleware('signed')->name('zips.progress');
    Route::get('/zips/{id}/download', [ZipController::class, 'download'])->middleware('signed')->name('zips.download');

    Route::get('/action-tokens/approve/{purpose}/{token}', [TokenApprovalController::class, 'update'])
        ->name('tokens.update');

    Route::post('/action-tokens/approve/{purpose}/{token}', [TokenApprovalController::class, 'store'])
        ->name('tokens.store');
});

Route::middleware('web')->group(function (): void {
    Route::post('/impersonation/{user}', [ImpersonationController::class, 'start'])
        ->middleware('auth:' . GuardEnum::DEFAULT->value)
        ->whereNumber('user')
        ->name('impersonation.start');
    Route::post('/impersonation/stop', [ImpersonationController::class, 'stop'])
        ->name('impersonation.stop');
});
