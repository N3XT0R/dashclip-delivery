<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\ActionToken;
use Illuminate\Database\Eloquent\Model;

final class ActionTokenRepository
{
    /**
     *
     * @param string $purpose
     * @param string $tokenHash
     * @param Model|null $subject
     * @param int|null $issuedForUserId
     * @param \DateTimeInterface|null $expiresAt
     * @param array|null $meta
     * @return ActionToken
     */
    public function create(
        string $purpose,
        string $tokenHash,
        ?Model $subject = null,
        ?int $issuedForUserId = null,
        ?\DateTimeInterface $expiresAt = null,
        ?array $meta = null
    ): ActionToken {
        return ActionToken::create([
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'purpose' => $purpose,
            'token_hash' => $tokenHash,
            'issued_for_user_id' => $issuedForUserId,
            'expires_at' => $expiresAt,
            'meta' => $meta,
        ]);
    }

    public function findValid(
        string $purpose,
        string $tokenHash
    ): ?ActionToken {
        return ActionToken::query()
            ->where('purpose', $purpose)
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function markUsed(ActionToken $token): void
    {
        $token->forceFill([
            'used_at' => now(),
        ])->save();
    }

    public function deleteExpired(): int
    {
        return ActionToken::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }
}
