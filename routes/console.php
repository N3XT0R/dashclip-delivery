<?php


use App\Console\Commands;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(Commands\WeeklyRun::class)
    ->mondays()
    ->at('08:00');

Schedule::command(Commands\AssignExpire::class)
    ->dailyAt('03:00');

Schedule::command(Commands\AssignUploader::class)->everyTenMinutes();

Schedule::command(Commands\RefreshDropboxToken::class)
    ->everyMinute();

Schedule::command('mail:scan-replies')->everyTenMinutes();
Schedule::command('clean:database')->dailyAt('02:00');
Schedule::command('assign:videos-to-teams')->everyFifteenMinutes();

//video processing
Schedule::command('video:process-videos')->everyMinute();

Schedule::command(Commands\VideoProcessing\RequeueStaleRunningCommand::class)->everyFifteenMinutes();
Schedule::command(Commands\VideoProcessing\RequeueFailedVideosCommand::class)->everyFifteenMinutes();
