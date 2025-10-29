<?php

declare(strict_types=1);

namespace App\Console\Commands\Traits;

use App\Facades\Cfg;
use Closure;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Command\Command as CommandAlias;

trait LockJobTrait
{
    /**
     * Base key for the lock.
     * Default: namespaced class name (e.g., App:Console:Commands:MyCommand)
     */
    protected ?string $lockKey = null;

    /**
     * Optional cache store (e.g., 'redis'). Null = default store from config/cache.php
     */
    protected ?string $lockStore = null;

    /** Getters / Setters */
    public function getLockKey(): string
    {
        return $this->lockKey ?? str_replace('\\', ':', static::class);
    }

    public function setLockKey(string $key): void
    {
        $this->lockKey = $key;
    }

    public function getLockStore(): ?string
    {
        return $this->lockStore;
    }

    public function setLockStore(?string $store): void
    {
        $this->lockStore = $store;
    }

    /**
     * Compose the actual lock name (optionally with a suffix, e.g., tenant/shard).
     */
    protected function lockName(?string $suffix = null): string
    {
        $base = $this->getLockKey();
        return $suffix ? "{$base}:{$suffix}" : $base;
    }

    /**
     * Create the Lock object (not acquired yet).
     */
    protected function makeLock(int $ttlSeconds = 600, ?string $suffix = null): Lock
    {
        $key = $this->lockName($suffix);

        return $this->lockStore
            ? Cache::store($this->lockStore)->lock($key, $ttlSeconds)
            : Cache::lock($key, $ttlSeconds);
    }

    /**
     * Try to acquire the lock immediately (non-blocking).
     * Returns the callback result on success and always releases the lock.
     *
     * @param  Closure  $callback  receives the acquired Lock as the first parameter
     * @return mixed|null null if the lock is already held by someone else
     */
    protected function tryWithLock(Closure $callback, int $ttlSeconds = 600, ?string $suffix = null): mixed
    {
        $lock = $this->makeLock($ttlSeconds, $suffix);

        if (!$lock->get()) {
            // Someone else already holds the lock
            return null;
        }

        try {
            return $callback($lock);
        } finally {
            // Ensure we always release the lock even if the callback throws
            $lock->release();
        }
    }

    /**
     * Wait up to $waitSeconds to acquire the lock (blocking).
     * Uses the built-in block() API; the lock is released automatically
     * after the callback finishes.
     *
     * @param  Closure  $callback  receives the acquired Lock as the first parameter
     * @return mixed
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException if not acquired in time
     */
    protected function blockWithLock(
        Closure $callback,
        int $waitSeconds = 30,
        int $ttlSeconds = 600,
        ?string $suffix = null
    ) {
        $lock = $this->makeLock($ttlSeconds, $suffix);

        return $lock->block($waitSeconds, function () use ($callback, $lock) {
            return $callback($lock);
        });
    }

    /**
     * Manually release a lock (e.g., from an admin command).
     * Caution: forceRelease() will break a foreign lock if one is currently held.
     */
    protected function forceUnlock(?string $suffix = null): void
    {
        $this->makeLock(0, $suffix)->forceRelease();
    }

    /**
     * Run a job wrapped with locking logic.
     *
     * @param  callable  $callback  The actual job logic to execute (receives $inbox, $disk)
     * @param  array  $options  Optional CLI-like options ['inbox' => ..., 'disk' => ..., 'ttl' => ..., 'wait' => ..., 'lock-store' => ...]
     * @param  string  $abortMsg  Message to display when lock is already held
     * @return int
     * @throws LockTimeoutException
     */
    public function handleLockedJob(
        callable $callback,
        array $options = [],
        string $abortMsg = 'Another job is already running. Abort.'
    ): int {
        // Optional lock store (e.g., redis)
        if (!empty($options['lock-store'])) {
            $this->setLockStore((string)$options['lock-store']);
        }

        $inbox = rtrim((string)($options['inbox'] ?? $this->option('inbox')), '/');
        $disk = (string)($options['disk'] ??
            $this->option('disk') ??
            Cfg::get('default_file_system', 'default', 'dropbox'));

        $ttl = (int)($options['ttl'] ?? $this->option('ttl') ?? 30);
        $waitSec = (int)($options['wait'] ?? $this->option('wait') ?? 0);

        // blocking mode: wait up to N seconds to acquire the lock
        if ($waitSec > 0) {
            return (int)$this->blockWithLock(
                fn(Lock $lock) => $callback($inbox, $disk),
                $waitSec,
                $ttl
            );
        }

        // non-blocking mode
        $result = $this->tryWithLock(
            fn(Lock $lock) => $callback($inbox, $disk),
            $ttl
        );

        if ($result === null) {
            $this->info($abortMsg);
            return CommandAlias::SUCCESS;
        }

        return (int)$result;
    }
}
