<?php

declare(strict_types=1);

namespace App\ValueObjects;

final class ImportStats
{
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $warnings = 0
    ) {
    }

    public static function empty(): self
    {
        return new self();
    }

    public function incrementCreated(): void
    {
        $this->created++;
    }

    public function incrementUpdated(): void
    {
        $this->updated++;
    }

    public function incrementWarnings(): void
    {
        $this->warnings++;
    }

    public function merge(self $other): void
    {
        $this->created += $other->created;
        $this->updated += $other->updated;
        $this->warnings += $other->warnings;
    }

    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'warnings' => $this->warnings,
        ];
    }
}
