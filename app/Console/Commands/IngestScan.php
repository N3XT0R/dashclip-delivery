<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Traits\LockJobTrait;
use App\Facades\Cfg;
use App\Services\Ingest\IngestScanner;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Lock;

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

    public function handle(): int
    {
        // Optionally select a specific cache store (e.g., redis) at runtime
        if ($store = (string)($this->option('lock-store') ?? '')) {
            $this->setLockStore($store);
        }

        $inbox = rtrim((string)$this->option('inbox'), '/');
        $disk = (string)($this->option('disk') ?: Cfg::get('default_file_system', 'default', 'dropbox'));
        $ttl = (int)$this->option('ttl');
        $waitSec = (int)$this->option('wait');

        // BLOCKING mode: wait up to --wait seconds to acquire the lock
        if ($waitSec > 0) {
            return (int)$this->blockWithLock(function (Lock $lock) use ($inbox, $disk) {
                return $this->runIngest($inbox, $disk);
            }, $waitSec, $ttl);
        }

        // NON-BLOCKING mode: try to acquire immediately; bail out if already running
        $result = $this->tryWithLock(function (Lock $lock) use ($inbox, $disk) {
            return $this->runIngest($inbox, $disk);
        }, $ttl);

        if ($result === null) {
            $this->info('Another ingest task is running. Abort.');
            return self::SUCCESS;
        }

        return (int)$result;
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