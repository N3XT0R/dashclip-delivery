<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Enum\TokenPurposeEnum;
use App\Models\ActionToken;
use App\Models\User;
use App\Services\ActionTokenService;
use Illuminate\Support\Carbon;
use Tests\DatabaseTestCase;

class ActionTokenServiceTest extends DatabaseTestCase
{
    protected ActionTokenService $actionTokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actionTokenService = $this->app->make(ActionTokenService::class);
    }

    public function testIssueCreatesActionToken(): void
    {
        $user = User::factory()->create();
        $expiresAt = Carbon::now()->addHour();

        $plainToken = $this->actionTokenService->issue(
            purpose: TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL,
            issuedForUser: $user,
            expiresAt: $expiresAt,
            meta: ['foo' => 'bar'],
        );

        $this->assertIsString($plainToken);
        $this->assertNotEmpty($plainToken);

        $this->assertDatabaseHas(ActionToken::class, [
            'purpose' => TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL->value,
            'issued_for_user_id' => $user->getKey(),
        ]);
    }

    public function testConsumeValidTokenMarksTokenAsUsed(): void
    {
        $plainToken = $this->actionTokenService->issue(
            purpose: TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL,
        );

        $token = $this->actionTokenService->consume(
            TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL,
            $plainToken
        );

        $this->assertNotNull($token);
        $this->assertInstanceOf(ActionToken::class, $token);
        $this->assertNotNull($token->used_at);

        $tokenConsumedAgain = $this->actionTokenService->consume(
            TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL,
            $plainToken
        );

        $this->assertNull($tokenConsumedAgain);
    }

    public function testConsumeReturnsNullForInvalidToken(): void
    {
        $token = $this->actionTokenService->consume(
            TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL,
            'invalid-token'
        );

        $this->assertNull($token);
    }

    public function testExpiredTokenCannotBeConsumed(): void
    {
        $plainToken = $this->actionTokenService->issue(
            purpose: TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL,
            expiresAt: now()->subMinute()
        );

        $token = $this->actionTokenService->consume(
            TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL,
            $plainToken
        );

        $this->assertNull($token);
    }

    public function testCleanupDeletesExpiredTokens(): void
    {
        $this->actionTokenService->issue(
            purpose: TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL,
            expiresAt: now()->subMinute()
        );

        $this->actionTokenService->issue(
            purpose: TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL,
            expiresAt: now()->addHour()
        );

        $deleted = $this->actionTokenService->cleanupExpired();

        $this->assertTrue($deleted);
        $this->assertDatabaseCount(ActionToken::class, 1);
    }

    public function testCleanupDeletesOrphanedTokens(): void
    {
        ActionToken::factory()->create([
            'subject_type' => 'App\\Models\\NonExistingModel',
            'subject_id' => 999999,
        ]);

        $deleted = $this->actionTokenService->cleanupExpired();

        $this->assertTrue($deleted);
        $this->assertDatabaseCount(ActionToken::class, 0);
    }
}
