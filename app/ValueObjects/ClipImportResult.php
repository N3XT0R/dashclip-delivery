<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\Clip;
use Illuminate\Support\Collection;

final class ClipImportResult
{
    public function __construct(
        public ImportStats $stats,
        /** @var Collection<int, Clip> */
        public Collection $created,
        /** @var Collection<int, Clip> */
        public Collection $updated
    ) {
    }

    public static function empty(): self
    {
        return new self(
            ImportStats::empty(),
            collect(),
            collect(),
        );
    }

    public function addCreated(Clip $clip): void
    {
        $this->stats->incrementCreated();
        $this->created->push($clip);
    }

    public function addUpdated(Clip $clip): void
    {
        $this->stats->incrementUpdated();
        $this->updated->push($clip);
    }

    public function incrementWarnings(): void
    {
        $this->stats->incrementWarnings();
    }

    public function toArray(): array
    {
        return [
            'stats' => $this->stats->toArray(),
            'created_ids' => $this->created->pluck('id')->all(),
            'updated_ids' => $this->updated->pluck('id')->all(),
        ];
    }
}
