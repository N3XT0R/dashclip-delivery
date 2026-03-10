<?php


use App\Console\Commands;
use Illuminate\Support\Facades\Schedule;

Schedule::command(Commands\WeeklyRun::class)
    ->mondays()
    ->at('08:00');

Schedule::command(Commands\AssignExpire::class)
    ->dailyAt('03:00');

Schedule::command(Commands\AssignUploader::class)->everyTenMinutes();

Schedule::command(Commands\RefreshDropboxToken::class)
    ->everyMinute();

Schedule::command(Commands\ScanMailReplies::class)->everyTenMinutes();
Schedule::command(Commands\CleanUpDatabaseCommand::class)->dailyAt('02:00');
Schedule::command(Commands\AssignVideosToTeams::class)->everyFifteenMinutes();

//video processing
Schedule::command(Commands\VideoProcessing\RequeueStaleRunningCommand::class)->everyFifteenMinutes();
Schedule::command(Commands\VideoProcessing\RequeueFailedVideosCommand::class)->everyFifteenMinutes();
