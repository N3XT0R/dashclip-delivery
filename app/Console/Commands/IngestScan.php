<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Traits\LockJobTrait;
use App\Facades\Cfg;
use App\Services\Ingest\IngestScanner;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;

class IngestScan extends Command
{
    use LockJobTrait;

    protected $signature = 'ingest:scan
        {--inbox=/srv/ingest/pending : Root directory of uploads (recursive)}
        {--disk= : Target storage disk (e.g., dropbox|local)}
        {--wait=0 : Seconds to wait for the lock (0 = non-blocking)}
        {--ttl=900 : Lock TTL in seconds}
        {--lock-store= : Optional cache store (e.g., redis)}';

    protected $description = 'Recursively scans the inbox, deduplicates files via SHA-256, and moves them to the configured storage using content addressing.';

    public function __construct(
        private readonly IngestScanner $scanner
    ) {
        parent::__construct();

        // Use the same lock key as ingest:unzip so both cannot run in parallel
        $this->setLockKey('ingest:lock');
    }

    /**
     * @throws LockTimeoutException
     */
    public function handle(): int
    {
        $this->applyLockStore($this->option('lock-store'));
        $inbox = rtrim((string)$this->option('inbox'), '/');
        $disk = (string)($this->option('disk') ?: Cfg::get('default_file_system', 'default', 'dropbox'));
        $ttl = (int)$this->option('ttl');
        $wait = (int)$this->option('wait');

        return $this->runWithLockFlow(
            fn(Lock $lock) => $this->runIngest($inbox, $disk),
            $ttl,
            $wait,
            'Another ingest task is running. Abort.'
        );
    }

    private function runIngest(string $inbox, string $diskName): int
    {
        $this->info('started...');

        // Provide console output handle to the service if it supports it
        $this->scanner->setOutput($this->getOutput());

        try {
            $stats = $this->scanner->scanDisk($inbox, $diskName);
            $this->info(sprintf('ingest done. %s disk=%s', $stats->__toString(), $diskName));
            return self::SUCCESS;
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}