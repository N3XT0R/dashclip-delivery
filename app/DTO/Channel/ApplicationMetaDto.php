<?php

declare(strict_types=1);

namespace App\DTO\Channel;

use Carbon\Carbon;

readonly class ApplicationMetaDto
{
    public function __construct(
        public array $channel = [],
        public bool $tosAccepted = false,
        public ?Carbon $tosAcceptedAt = null,
    ) {
    }

    public static function fromMetaArray(array $data): self
    {
        return new self(
            channel: $data['new_channel'] ?? [],
            tosAccepted: $data['tos_accepted'] ?? false,
            tosAcceptedAt: isset($data['tos_accepted_at']) ? Carbon::parse($data['tos_accepted_at']) : null,
        );
    }
}