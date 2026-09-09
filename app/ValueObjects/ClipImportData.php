<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Sanitised, resolved data for a single clip row of an info.csv import.
 *
 * Bundles the per-row values that {@see \App\Services\InfoImporter} writes to a
 * clip so the create/update helpers take one structured argument instead of a
 * long positional list.
 */
final readonly class ClipImportData
{
    public function __construct(
        public ?int $startSec,
        public ?int $endSec,
        public string $note,
        public string $bundle,
        public string $role,
        public string $submittedBy,
        public string $preferredChannel,
        public ?int $preferredChannelId,
    ) {
    }

    /**
     * Clip attributes for a fresh row, empty strings collapsed to null.
     *
     * @return array<string, int|string|null>
     */
    public function toClipAttributes(): array
    {
        return [
            'start_sec' => $this->startSec,
            'end_sec' => $this->endSec,
            'note' => $this->note !== '' ? $this->note : null,
            'bundle_key' => $this->bundle !== '' ? $this->bundle : null,
            'role' => $this->role !== '' ? $this->role : null,
            'submitted_by' => $this->submittedBy !== '' ? $this->submittedBy : null,
            'preferred_channel' => $this->preferredChannel !== '' ? $this->preferredChannel : null,
            'preferred_channel_id' => $this->preferredChannelId,
        ];
    }
}
