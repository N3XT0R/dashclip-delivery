<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Channel;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Channel;
use App\Models\User;
use App\Repository\ChannelRepository;
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

    public function testAddUserToChannelCleansUpWhenRoleAssignmentFails(): void
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

    public function testApproveUserChannelAccessThrowsExceptionWhenUserLacksAccess(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User does not have access to the channel.');

        $user = User::factory()->create();
        $channel = Channel::factory()->create();

        $this->service->approveUserChannelAccess($user, $channel);
    }

    public function testApproveUserChannelAccessMarksUserVerified(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();
        $channelRepository = $this->app->make(ChannelRepository::class);

        $channelRepository->assignUserToChannel($user, $channel);

        $this->service->approveUserChannelAccess($user, $channel);

        $this->assertTrue(
            $channelRepository->isUserVerifiedForChannel($user, $channel)
        );
    }

    public function testRevokeUserChannelAccessRemovesRoleWhenNoChannelsLeft(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();
        $channelRepository = $this->app->make(ChannelRepository::class);
        $roleRepository = $this->app->make(RoleRepository::class);

        $this->service->addUserToChannel($user, $channel);
        $channelRepository->setUserVerifiedForChannel($user, $channel);

        $this->service->revokeUserChannelAccess($user, $channel);

        $this->assertFalse(
            $channelRepository->hasUserAccessToChannel($user, $channel)
        );
        $this->assertFalse(
            $roleRepository->hasRole($user, RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD)
        );
    }

    public function testRevokeUserChannelAccessKeepsRoleWithOtherVerifiedChannels(): void
    {
        $user = User::factory()->create();
        $primaryChannel = Channel::factory()->create();
        $secondaryChannel = Channel::factory()->create();
        $channelRepository = $this->app->make(ChannelRepository::class);
        $roleRepository = $this->app->make(RoleRepository::class);

        $this->service->addUserToChannel($user, $primaryChannel);
        $this->service->addUserToChannel($user, $secondaryChannel);

        $channelRepository->setUserVerifiedForChannel($user, $primaryChannel);
        $channelRepository->setUserVerifiedForChannel($user, $secondaryChannel);

        $this->service->revokeUserChannelAccess($user, $secondaryChannel);

        $this->assertFalse(
            $channelRepository->hasUserAccessToChannel($user, $secondaryChannel)
        );
        $this->assertTrue(
            $channelRepository->hasUserAccessToChannel($user, $primaryChannel)
        );
        $this->assertTrue(
            $roleRepository->hasRole($user, RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD)
        );
    }

    public function testIsUserChannelOperatorRequiresVerification(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();
        $channelRepository = $this->app->make(ChannelRepository::class);

        $this->service->addUserToChannel($user, $channel);

        $this->assertFalse(
            $this->service->isUserChannelOperator($user, $channel)
        );

        $channelRepository->setUserVerifiedForChannel($user, $channel);

        $this->assertTrue(
            $this->service->isUserChannelOperator($user, $channel)
        );
    }

    public function testIsUserChannelOperatorRequiresRole(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();
        $channelRepository = $this->app->make(ChannelRepository::class);
        $roleRepository = $this->app->make(RoleRepository::class);

        $this->service->addUserToChannel($user, $channel);
        $channelRepository->setUserVerifiedForChannel($user, $channel);
        $roleRepository->removeRoleFromUser($user, RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD);

        $this->assertFalse(
            $this->service->isUserChannelOperator($user, $channel)
        );
    }

    public function testIsChannelEmailOwnerChannelOperatorReturnsFalseWithoutMatchingUser(): void
    {
        $channel = Channel::factory()->create(['email' => 'missing@example.com']);

        $this->assertFalse(
            $this->service->isChannelEmailOwnerChannelOperator($channel)
        );
    }

    public function testIsChannelEmailOwnerChannelOperatorReturnsFalseWhenUserIsNotOperator(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $channel = Channel::factory()->create(['email' => $user->email]);

        $this->assertFalse(
            $this->service->isChannelEmailOwnerChannelOperator($channel)
        );
    }

    public function testIsChannelEmailOwnerChannelOperatorReturnsTrueWhenUserIsVerifiedOperator(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $channel = Channel::factory()->create(['email' => $user->email]);
        $channelRepository = $this->app->make(ChannelRepository::class);

        $this->service->addUserToChannel($user, $channel);
        $channelRepository->setUserVerifiedForChannel($user, $channel);

        $this->assertTrue(
            $this->service->isChannelEmailOwnerChannelOperator($channel)
        );
    }
}
