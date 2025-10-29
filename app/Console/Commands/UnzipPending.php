<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Traits\LockJobTrait;
use App\Services\Contracts\UnzipServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use InvalidArgumentException;

use function count;

class UnzipPending extends Command
{
    use LockJobTrait;

    protected $signature = 'ingest:unzip
        {--inbox=/srv/ingest/pending : Directory containing ZIP files}
        {--wait=0 : Seconds to wait for the lock (0 = non-blocking)}
        {--ttl=600 : Lock TTL in seconds}
        {--lock-store= : Optional cache store (e.g. redis)}';

    protected $description = 'Extracts ZIP files in the given directory and removes them after extraction';

    public function __construct(
        private readonly UnzipServiceInterface $service
    ) {
        parent::__construct();
        $this->setLockKey('ingest:lock');
    }

    /**
     * @throws LockTimeoutException
     */
    public function handle(): int
    {
        $this->applyLockStore($this->option('lock-store'));

        $dir = rtrim((string)$this->option('inbox'), '/');
        $ttl = (int)$this->option('ttl');
        $wait = (int)$this->option('wait');

        return $this->runWithLockFlow(
            fn(Lock $lock) => $this->runExtraction($dir),
            $ttl,
            $wait,
            'Another ingest task is running. Abort.'
        );
    }

    /**
     * Thin orchestration: I/O, exceptions to console, and stats rendering.
     */
    protected function runExtraction(string $dir): int
    {
        try {
            $stats = $this->service->unzipDirectory($dir);

            // Render a concise summary
            $this->info(sprintf(
                'Done. total=%d extracted=%d failed=%d skipped=%d',
                $stats->total(),
                count($stats->extractedArchives),
                count($stats->failedArchives),
                count($stats->skippedArchives),
            ));

            // Optional verbose listing
            foreach ($stats->extractedArchives as $f) {
                $this->line("Extracted: {$f}");
            }
            foreach ($stats->skippedArchives as $f) {
                $this->warn("Skipped (no safe entries): {$f}");
            }
            foreach ($stats->failedArchives as $f) {
                $this->error("Failed: {$f}");
            }

            return self::SUCCESS;
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Unexpected error: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
