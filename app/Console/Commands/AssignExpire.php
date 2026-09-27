<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Constants\Config\DefaultConfigEntry;
use App\Facades\Cfg;
use App\Services\AssignmentExpirer;
use Illuminate\Console\Command;

class AssignExpire extends Command
{
    /**
     * Offers expire exactly as many days after the run that sent them, so the run that should hand
     * the video on would otherwise miss them by the seconds it needs to get there.
     */
    private const int DEFAULT_GRACE_MINUTES = 5;

    protected $signature = 'assign:expire {--cooldown-days=} {--grace-minutes=}';
    protected $description = 'Marks overdue assignments as expired and sets a cooldown for each (channel, video) pair.';

    public function __construct(private AssignmentExpirer $expirer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $cooldownDays = (int)$this->option('cooldown-days');
        if (0 === $cooldownDays) {
            $cooldownDays = (int)Cfg::get(
                DefaultConfigEntry::ASSIGN_EXPIRE_COOLDOWN_DAYS,
                'default',
                14
            );
        }
        $graceMinutes = $this->option('grace-minutes') === null
            ? self::DEFAULT_GRACE_MINUTES
            : max(0, (int)$this->option('grace-minutes'));

        $expiredCount = $this->expirer->expire($cooldownDays, $graceMinutes);
        $this->info("Expired: {$expiredCount}");
        return self::SUCCESS;
    }
}
