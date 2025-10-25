<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enum\Ingest\IngestResult;

final class IngestStats
{
    private int $new = 0;
    private int $dups = 0;
    private int $err = 0;

    public static function fromArray(array $data): self
    {
        $instance = new self();

        $instance->new = (int)($data['new'] ?? 0);
        $instance->dups = (int)($data['dups'] ?? 0);
        $instance->err = (int)($data['err'] ?? 0);

        return $instance;
    }

    public function increment(IngestResult $result): void
    {
        match ($result) {
            IngestResult::NEW => $this->new++,
            IngestResult::DUPS => $this->dups++,
            IngestResult::ERR => $this->err++,
        };
    }

    public function add(IngestStats $other): void
    {
        $this->new += $other->new;
        $this->dups += $other->dups;
        $this->err += $other->err;
    }

    public function total(): int
    {
        return $this->new + $this->dups + $this->err;
    }

    public function isEmpty(): bool
    {
        return $this->total() === 0;
    }

    public function toArray(): array
    {
        return [
            IngestResult::NEW->value => $this->new,
            IngestResult::DUPS->value => $this->dups,
            IngestResult::ERR->value => $this->err,
        ];
    }

    public function __toString(): string
    {
        return sprintf(
            'Neu: %d | Doppelt: %d | Fehler: %d',
            $this->new,
            $this->dups,
            $this->err
        );
    }

    public function getNew(): int
    {
        return $this->new;
    }

    public function getDups(): int
    {
        return $this->dups;
    }

    public function getErr(): int
    {
        return $this->err;
    }
}
