<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Models\User;
use App\Models\UserMailConfig;
use App\Repository\UserMailConfigRepository;
use Tests\DatabaseTestCase;

final class UserMailConfigRepositoryTest extends DatabaseTestCase
{
    public function testGetAndSetNotificationPreference(): void
    {
        $user = User::factory()->create();
        $repository = app(UserMailConfigRepository::class);

        $repository->setForUser($user, 'test.notification', false);

        $this->assertDatabaseHas(UserMailConfig::class, [
            'user_id' => $user->id,
            'key' => 'test.notification',
            'value' => false,
        ]);

        $this->assertFalse($repository->getForUser($user, 'test.notification'));
    }

    public function testIsAllowedUsesDefaultWhenMissing(): void
    {
        $user = User::factory()->create();
        $repository = app(UserMailConfigRepository::class);

        $this->assertTrue($repository->isAllowed($user, 'missing.notification', true));
        $this->assertFalse($repository->isAllowed($user, 'missing.notification', false));
    }

    public function testAllForUserReturnsStoredPreferences(): void
    {
        $user = User::factory()->create();
        $repository = app(UserMailConfigRepository::class);

        $repository->setForUser($user, 'first.notification', true);
        $repository->setForUser($user, 'second.notification', false);

        $this->assertSame(
            [
                'first.notification' => true,
                'second.notification' => false,
            ],
            $repository->allForUser($user)
        );
    }
}
