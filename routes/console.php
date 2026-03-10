<?php


use App\Console\Commands;
use Illuminate\Support\Facades\Schedule;

# general
Schedule::command(Commands\WeeklyRun::class)
    ->mondays()
    ->at('08:00');

Schedule::command(Commands\AssignExpire::class)
    ->dailyAt('03:00');

# Ingest
Schedule::command(Commands\AssignUploader::class)->everyTenMinutes();
Schedule::command(Commands\AssignVideosToTeams::class)->everyFifteenMinutes();

# Dropbox
Schedule::command(Commands\RefreshDropboxToken::class)
    ->everyMinute();

# Mail
Schedule::command(Commands\ScanMailReplies::class)->everyTenMinutes();

# Cleanup
Schedule::command(Commands\CleanUpDatabaseCommand::class)->dailyAt('02:00');

# video processing
Schedule::command(Commands\VideoProcessing\RequeueStaleRunningCommand::class)->everyFifteenMinutes();
Schedule::command(Commands\VideoProcessing\RequeueFailedVideosCommand::class)->everyFifteenMinutes();
