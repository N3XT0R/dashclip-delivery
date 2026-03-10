<?php

use App\Console\Commands\VideoProcessing\RequeueFailedVideosCommand;
use App\Console\Commands\VideoProcessing\RequeueStaleRunningCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('weekly:run')
    ->mondays()
    ->at('08:00');

Schedule::command('assign:expire')
    ->dailyAt('03:00');

Schedule::command('assign:uploader')->everyTenMinutes();

Schedule::command('dropbox:refresh-token')
    ->everyMinute();

Schedule::command('mail:scan-replies')->everyTenMinutes();
Schedule::command('clean:database')->dailyAt('02:00');
Schedule::command('assign:videos-to-teams')->everyFifteenMinutes();

//video processing
Schedule::command('video:process-videos')->everyMinute();

Schedule::command(RequeueStaleRunningCommand::class)->everyFifteenMinutes();
Schedule::command(RequeueFailedVideosCommand::class)->everyFifteenMinutes();
