<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Traits\LockJobTrait;
use App\Services\Ingest\IngestScanner;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\LockTimeoutException;

class IngestScan extends Command
{
    use LockJobTrait;

    protected $signature = 'ingest:new-scan
        {--inbox=/srv/ingest/pending : Root directory of uploads (recursive)}
        {--disk= : Target storage disk (e.g., dropbox|local)}
        {--wait=0 : Seconds to wait for the lock (0 = non-blocking)}
        {--ttl=900 : Lock TTL in seconds}
        {--lock-store= : Optional cache store (e.g., redis)}';

    protected $description = 'Scannt Inbox rekursiv, dedupe per SHA-256, verschiebt content-addressiert auf konfiguriertes Storage.';

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
        return $this->handleLockedJob(
            fn(string $inbox, string $disk) => $this->runIngest($inbox, $disk),
            options: [
                'inbox' => $this->option('inbox'),
                'disk' => $this->option('disk'),
                'ttl' => $this->option('ttl'),
                'wait' => $this->option('wait'),
                'lock-store' => $this->option('lock-store'),
            ],
            abortMsg: 'Another ingest task is running. Abort.'
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