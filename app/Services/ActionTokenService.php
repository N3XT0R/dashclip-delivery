<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActionToken;
use App\Models\User;
use App\Repository\ActionTokenRepository;
use Illuminate\Database\Eloquent\Model;
use Random\RandomException;

final readonly class ActionTokenService
{
    public function __construct(
        private ActionTokenRepository $repository
    ) {
    }

    /**
     *
     * @param string $purpose
     * @param Model|null $subject
     * @param User|null $issuedForUser
     * @param \DateTimeInterface|null $expiresAt
     * @param array|null $meta
     * @return string
     * @throws RandomException
     */
    public function issue(
        string $purpose,
        ?Model $subject = null,
        ?User $issuedForUser = null,
        ?\DateTimeInterface $expiresAt = null,
        ?array $meta = null
    ): string {
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $this->repository->create(
            purpose: $purpose,
            tokenHash: $tokenHash,
            subject: $subject,
            issuedForUserId: $issuedForUser?->getKey(),
            expiresAt: $expiresAt,
            meta: $meta
        );

        return $plainToken;
    }

    public function consume(
        string $purpose,
        string $plainToken
    ): ?ActionToken {
        $tokenHash = hash('sha256', $plainToken);

        $token = $this->repository->findValid(
            purpose: $purpose,
            tokenHash: $tokenHash
        );

        if (!$token) {
            return null;
        }

        $this->repository->markUsed($token);

        return $token;
    }

    public function cleanupExpired(): int
    {
        return $this->repository->deleteExpired();
    }
}
