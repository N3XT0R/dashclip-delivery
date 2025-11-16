<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AssignmentDistributor;
use Illuminate\Console\Command;
use RuntimeException;

class AssignDistribute extends Command
{
    protected $signature = 'assign:distribute {--quota=}';
    protected $description = 'Fairly distributes new and expired videos across channels (round-robin, weighted, weekly quota).';

    public function __construct(private AssignmentDistributor $distributor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $exitCode = self::SUCCESS;
        try {
            $quota = $this->option('quota');
            $stats = $this->distributor->distribute($quota !== null ? (int)$quota : null);
            $this->info("Assigned={$stats['assigned']}, skipped={$stats['skipped']}");
        } catch (RuntimeException $e) {
            $this->warn($e->getMessage());
            $exitCode = self::FAILURE;
        }
        return $exitCode;
    }
}
