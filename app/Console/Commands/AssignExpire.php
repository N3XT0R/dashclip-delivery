<?php

declare(strict_types=1);

// app/Console/Commands/AssignExpire.php
namespace App\Console\Commands;

use App\Facades\Cfg;
use App\Services\AssignmentExpirer;
use Illuminate\Console\Command;

class AssignExpire extends Command
{
    protected $signature = 'assign:expire {--cooldown-days=}';
    protected $description = 'Marks overdue assignments as expired and sets a cooldown for each (channel, video) pair.';

    public function __construct(private AssignmentExpirer $expirer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $cooldownDays = (int)$this->option('cooldown-days');
        if (0 === $cooldownDays) {
            $cooldownDays = (int)Cfg::get('assign_expire_cooldown_days', 'default', 14);
        }
        $expiredCount = $this->expirer->expire($cooldownDays);
        $this->info("Expired: {$expiredCount}");
        return self::SUCCESS;
    }
}
