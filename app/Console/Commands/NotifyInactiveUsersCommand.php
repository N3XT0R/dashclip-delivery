<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\InactivityReminderService;
use Illuminate\Console\Command;

class NotifyInactiveUsersCommand extends Command
{
    protected $signature = 'notify:inactive-users {--days=7}';
    protected $description = 'Sends an inactivity reminder to users who have not logged in for a while.';

    public function __construct(private InactivityReminderService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = $this->service->notify((int) $this->option('days'));
        $this->info("Inactivity reminders sent: {$count}");

        return self::SUCCESS;
    }
}
