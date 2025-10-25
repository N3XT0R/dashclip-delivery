<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\Clip;
use App\Models\Video;
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

    public function merge(self $other): void
    {
        $this->stats->merge($other->stats);
        $this->created = $this->created->merge($other->created);
        $this->updated = $this->updated->merge($other->updated);
    }

    public function clipsForVideo(Video $video): Collection
    {
        $id = (int)$video->getKey();
        return $this->created
            ->merge($this->updated)
            ->filter(fn(Clip $clip) => (int)$clip->video_id === $id)
            ->values();
    }

    public function allClips(): Collection
    {
        return $this->created->merge($this->updated)->values();
    }
}
