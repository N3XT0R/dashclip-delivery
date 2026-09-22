<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WeeklyRun extends Command
{
    protected $signature = 'weekly:run';
    protected $description = 'expire, distribute, mark finished videos, notify';

    public function handle(): int
    {
        $this->call('assign:uploader');
        $this->call('assign:expire');
        $this->call('assign:distribute');
        $this->call('videos:mark-distributed');
        if (defined('IS_TESTING') || app()->environment('production')) {
            $this->call('notify:offers');
        }

        return self::SUCCESS;
    }
}
