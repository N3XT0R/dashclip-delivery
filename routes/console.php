<?php


use App\Console\Commands;
use Illuminate\Support\Facades\Schedule;

# general
Schedule::command(Commands\WeeklyRun::class)
    ->mondays()
    ->at('08:00');

Schedule::command(Commands\AssignExpire::class)
    ->dailyAt('03:00');

# Inactivity
Schedule::command(Commands\NotifyInactiveUsersCommand::class)->dailyAt('09:00');

# horizon
Schedule::command('horizon:snapshot')->everyFiveMinutes();

# Ingest
Schedule::command(Commands\AssignUploader::class)->everyTenMinutes();
Schedule::command(Commands\AssignVideosToTeams::class)->everyFifteenMinutes();

# Dropbox
Schedule::command(Commands\RefreshDropboxToken::class)
    ->everyMinute();

# Mail
Schedule::command(Commands\ScanMailReplies::class)->everyTenMinutes();

# Blog
Schedule::command(Commands\PublishScheduledPostsCommand::class)->everyMinute()->withoutOverlapping();

# Cleanup
Schedule::command(Commands\CleanUpDatabaseCommand::class)->dailyAt('02:00');
Schedule::command(Commands\CleanFfmpegTmpCommand::class)->hourly();
Schedule::command(Commands\CleanExpiredZipsCommand::class)->hourly()->withoutOverlapping();
Schedule::command(Commands\PurgeDeletedVideosCommand::class)->dailyAt('04:00')->withoutOverlapping();

# WebDAV ingest
Schedule::command(Commands\IngestWebDavCommand::class)->everyFifteenMinutes();
Schedule::command(Commands\CleanWebDavNonZipCommand::class)->hourly();

# video processing
Schedule::command(Commands\VideoProcessing\RequeueStaleRunningCommand::class)->everyFifteenMinutes();
Schedule::command(Commands\VideoProcessing\RequeueFailedVideosCommand::class)->everyFifteenMinutes();
Schedule::command(Commands\VideoProcessing\RequeueNeverRanVideosCommand::class)->everyFifteenMinutes();
Schedule::command(Commands\VideoProcessing\RequeueMissingIngestStepsCommand::class)->daily();
