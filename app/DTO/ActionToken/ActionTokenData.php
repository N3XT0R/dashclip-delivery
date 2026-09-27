<?php

declare(strict_types=1);

namespace App\DTO\ActionToken;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Everything one action token is stored with.
 */
final readonly class ActionTokenData
{
    /**
     * @param string $purpose what the token may be used for
     * @param string $tokenHash the hashed token, the plain one is never stored
     * @param Model|null $subject the record the token belongs to
     * @param int|null $issuedForUserId the account the token was issued for
     * @param DateTimeInterface|null $expiresAt when the token stops working
     * @param array<string, mixed>|null $meta anything the purpose needs on top
     */
    public function __construct(
        public string $purpose,
        public string $tokenHash,
        public ?Model $subject = null,
        public ?int $issuedForUserId = null,
        public ?DateTimeInterface $expiresAt = null,
        public ?array $meta = null,
    ) {
    }
}
