<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\ActionToken;
use Illuminate\Database\Eloquent\Model;

final class ActionTokenRepository
{
    /**
     * Create a new action token record.
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

    /**
     * Find a valid (not used, not expired) action token by purpose and token hash.
     * @param string $purpose
     * @param string $tokenHash
     * @return ActionToken|null
     */
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

    public function findByPurposeAndSubject(
        string $purpose,
        Model $subject
    ): ?ActionToken {
        return ActionToken::query()
            ->where('purpose', $purpose)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->first();
    }

    /**
     * Mark the given action token as used.
     * @param ActionToken $token
     * @return void
     */
    public function markUsed(ActionToken $token): void
    {
        $token->forceFill([
            'used_at' => now(),
        ])->save();
    }

    /**
     * Delete all expired action tokens.
     * @return bool
     */
    public function deleteExpired(): bool
    {
        return ActionToken::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }

    /**
     * Delete orphaned action tokens (tokens with subjects that no longer exist).
     * @return int Number of tokens removed
     */
    public function deleteOrphans(): int
    {
        return ActionToken::query()
            ->whereNotNull('subject_type')
            ->whereNotNull('subject_id')
            ->get()
            ->filter(function (ActionToken $token): bool {
                $class = $token->subject_type;

                if (!class_exists($class)) {
                    return true;
                }

                if (!is_subclass_of($class, Model::class)) {
                    return true;
                }

                return !$class::query()
                    ->whereKey($token->subject_id)
                    ->exists();
            })
            ->each
            ->delete()
            ->count();
    }
}
