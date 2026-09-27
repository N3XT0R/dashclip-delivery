<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * What one distribution run did, and what it left behind.
 */
final readonly class DistributionRunResult
{
    /**
     * @param int $assigned videos that were offered to a channel
     * @param int $skipped videos no channel could take right now
     * @param int $deferred videos held back for their wished channel
     * @param int $waiting videos the run had no place left for
     * @param int $failed videos of an uploader the run could not serve at all
     */
    public function __construct(
        public int $assigned = 0,
        public int $skipped = 0,
        public int $deferred = 0,
        public int $waiting = 0,
        public int $failed = 0,
    ) {
    }

    public function plus(self $other): self
    {
        return new self(
            $this->assigned + $other->assigned,
            $this->skipped + $other->skipped,
            $this->deferred + $other->deferred,
            $this->waiting + $other->waiting,
            $this->failed + $other->failed,
        );
    }

    /**
     * @param int $failed
     * @return self
     */
    public function withFailed(int $failed): self
    {
        return new self($this->assigned, $this->skipped, $this->deferred, $this->waiting, $failed);
    }

    /**
     * @return array{assigned:int, skipped:int, deferred:int, waiting:int, failed:int}
     */
    public function toArray(): array
    {
        return [
            'assigned' => $this->assigned,
            'skipped' => $this->skipped,
            'deferred' => $this->deferred,
            'waiting' => $this->waiting,
            'failed' => $this->failed,
        ];
    }
}
