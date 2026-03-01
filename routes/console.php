<?php

use App\Facades\Cfg;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('weekly:run')
    ->mondays()
    ->at('08:00');

Schedule::command('ingest:scan', [
    '--inbox' => Cfg::get('ingest_inbox_absolute_path', 'default', '/srv/ingest/pending/', true),
])->hourly();

Schedule::command('ingest:unzip', [
    '--inbox' => Cfg::get('ingest_inbox_absolute_path', 'default', '/srv/ingest/pending/', true),
])->everyTenMinutes();

Schedule::command('assign:expire')
    ->dailyAt('03:00');

Schedule::command('assign:uploader')->everyTenMinutes();

Schedule::command('video:cleanup', [
    '--weeks' => Cfg::get('post_expiry_retention_weeks', 'default', 1, true),
])
    ->dailyAt('04:00');

Schedule::command('notify:reminders', [
    '--days' => Cfg::get('email_reminder_days', 'email', 1, true),
])
    ->dailyAt('09:00');

Schedule::command('dropbox:refresh-token')
    ->everyMinute();

Schedule::command('mail:scan-replies')->everyTenMinutes();
Schedule::command('clean:disk')->everyMinute();
Schedule::command('clean:database')->dailyAt('02:00');
Schedule::command('assign:videos-to-teams')->everyFifteenMinutes();

//video processing
Schedule::command('video:calculate-hash')->everyMinute();
