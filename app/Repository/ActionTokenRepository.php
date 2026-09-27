<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\ActionToken\ActionTokenData;
use App\Models\ActionToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;

final class ActionTokenRepository
{
    /**
     * Store a new action token.
     * @param ActionTokenData $data
     * @return ActionToken
     */
    public function create(ActionTokenData $data): ActionToken
    {
        return ActionToken::create([
            'subject_type' => $data->subject?->getMorphClass(),
            'subject_id' => $data->subject?->getKey(),
            'purpose' => $data->purpose,
            'token_hash' => $data->tokenHash,
            'issued_for_user_id' => $data->issuedForUserId,
            'expires_at' => $data->expiresAt,
            'meta' => $data->meta,
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
     * Get a lazy collection of action tokens that have expired (i.e. expires_at is in the past).
     * @return LazyCollection<ActionToken>
     */
    public function getExpiredTokens(): LazyCollection
    {
        return ActionToken::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now())
            ->lazyById();
    }

    /**
     * Get a lazy collection of action tokens that have an assigned subject
     * (i.e. subject_type and subject_id are not null).
     * @param int $chunkSize
     * @return LazyCollection<ActionToken>
     */
    public function getLazyActionTokensWithSubject(int $chunkSize = 100): LazyCollection
    {
        return ActionToken::query()
            ->whereNotNull('subject_id')
            ->whereNotNull('subject_type')
            ->lazyById(chunkSize: $chunkSize);
    }
}
