<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\TokenPurposeEnum;
use App\Models\ActionToken;
use App\Models\User;
use App\Repository\ActionTokenRepository;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Random\RandomException;

final readonly class ActionTokenService
{
    public function __construct(
        private ActionTokenRepository $repository
    ) {
    }

    /**
     * Issue a new action token.
     * @param string $purpose
     * @param Model|null $subject
     * @param User|null $issuedForUser
     * @param DateTimeInterface|null $expiresAt
     * @param array|null $meta
     * @return string
     * @throws RandomException
     */
    public function issue(
        TokenPurposeEnum $purpose,
        ?Model $subject = null,
        ?User $issuedForUser = null,
        ?DateTimeInterface $expiresAt = null,
        ?array $meta = null
    ): string {
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $this->repository->create(
            purpose: $purpose->value,
            tokenHash: $tokenHash,
            subject: $subject,
            issuedForUserId: $issuedForUser?->getKey(),
            expiresAt: $expiresAt,
            meta: $meta
        );

        return $plainToken;
    }

    /**
     * Consume an action token.
     * @param TokenPurposeEnum $purpose
     * @param string $plainToken
     * @return ActionToken|null
     */
    public function consume(
        TokenPurposeEnum $purpose,
        string $plainToken
    ): ?ActionToken {
        $tokenHash = hash('sha256', $plainToken);

        $token = $this->repository->findValid(
            purpose: $purpose->value,
            tokenHash: $tokenHash
        );

        if (!$token) {
            return null;
        }

        $this->repository->markUsed($token);

        return $token;
    }

    /**
     * Cleanup expired action tokens.
     * @return int
     */
    public function cleanupExpired(): int
    {
        return $this->repository->deleteExpired();
    }
}
