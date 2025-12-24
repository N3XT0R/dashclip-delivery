<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Channel;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Channel;
use App\Models\User;
use App\Repository\RoleRepository;
use App\Services\Channel\ChannelOperatorService;
use Tests\DatabaseTestCase;

final class ChannelOperatorServiceTest extends DatabaseTestCase
{
    private ChannelOperatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(ChannelOperatorService::class);
    }

    public function testAddUserToChannelAssignsChannelAndRole(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();

        $this->service->addUserToChannel($user, $channel);

        $this->assertTrue(
            $user->channels()->whereKey($channel->getKey())->exists()
        );

        $this->assertTrue(
            $user->hasRole(
                RoleEnum::CHANNEL_OPERATOR->value,
                GuardEnum::STANDARD->value
            )
        );
    }

    public function testAddUserToChannelRollbacksOnFailure(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();

        $roleRepo = $this->getMockBuilder(RoleRepository::class)
            ->onlyMethods(['giveRoleToUser'])
            ->enableOriginalConstructor()
            ->getMock();

        $roleRepo->expects($this->once())
            ->method('giveRoleToUser')
            ->willThrowException(new \Exception('Simulated failure'));

        $this->app->instance(RoleRepository::class, $roleRepo);

        $service = $this->app->make(ChannelOperatorService::class);

        try {
            $service->addUserToChannel($user, $channel);
            $this->fail('Exception was expected');
        } catch (\Exception) {
            // expected
        }

        $this->assertFalse(
            $user->channels()->whereKey($channel->getKey())->exists()
        );
    }


    public function testAddUserToChannelIsIdempotent(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();

        $this->service->addUserToChannel($user, $channel);
        $this->service->addUserToChannel($user, $channel);

        $this->assertSame(
            1,
            $user->channels()->whereKey($channel->getKey())->count()
        );

        $this->assertTrue(
            $user->hasRole(
                RoleEnum::CHANNEL_OPERATOR->value,
                GuardEnum::STANDARD->value
            )
        );
    }

    public function testRoleIsKeptWhenUserAlreadyHasChannelAccess(): void
    {
        $user = User::factory()->create();
        $channelA = Channel::factory()->create();
        $channelB = Channel::factory()->create();

        $this->service->addUserToChannel($user, $channelA);
        $this->service->addUserToChannel($user, $channelB);

        $this->assertSame(
            2,
            $user->channels()->count()
        );

        $this->assertTrue(
            $user->hasRole(
                RoleEnum::CHANNEL_OPERATOR->value,
                GuardEnum::STANDARD->value
            )
        );
    }
}
