<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Editable clip fields parsed from a single info.csv row.
 *
 * Groups the values that {@see \App\Services\InfoImporter} compares against an
 * existing clip and writes on create, so the create/update helpers take one
 * structured argument instead of a long positional list. Timing and role stay
 * separate because they identify the clip rather than describe it.
 */
final readonly class ClipImportData
{
    public function __construct(
        public string $note,
        public string $bundle,
        public string $submittedBy,
        public string $preferredChannel,
        public ?int $preferredChannelId,
    ) {
    }

    /**
     * Clip attributes for these fields, empty strings collapsed to null.
     *
     * @return array<string, int|string|null>
     */
    public function toClipAttributes(): array
    {
        return [
            'note' => $this->note !== '' ? $this->note : null,
            'bundle_key' => $this->bundle !== '' ? $this->bundle : null,
            'submitted_by' => $this->submittedBy !== '' ? $this->submittedBy : null,
            'preferred_channel' => $this->preferredChannel !== '' ? $this->preferredChannel : null,
            'preferred_channel_id' => $this->preferredChannelId,
        ];
    }
}
