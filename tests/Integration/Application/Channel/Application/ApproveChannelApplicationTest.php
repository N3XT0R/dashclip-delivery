<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Channel\Application;

use App\Application\Channel\Application\ApproveChannelApplication;
use App\Events\Channel\ChannelAccessRequested;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use App\Repository\ChannelRepository;
use App\Services\ChannelService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\DatabaseTestCase;

final class ApproveChannelApplicationTest extends DatabaseTestCase
{
    public function testApprovingExistingChannelApplicationAssignsUserAndDispatchesEvent(): void
    {
        Event::fake([ChannelAccessRequested::class]);
        Queue::fake();
        Mail::fake();

        $channel = Channel::factory()->create();
        $approver = User::factory()->create();
        $application = ChannelApplication::factory()
            ->forExistingChannel($channel)
            ->create();

        $useCase = $this->app->make(ApproveChannelApplication::class);
        $useCase->handle($application, $approver);

        $this->assertTrue(
            $channel->channelUsers()->whereKey($application->user->getKey())->exists()
        );

        Event::assertDispatched(
            ChannelAccessRequested::class,
            static fn(ChannelAccessRequested $event) => $event->channelApplication->is($application)
        );
    }

    public function testApprovingNewChannelApplicationCreatesChannelAndAssignsUser(): void
    {
        Event::fake([ChannelAccessRequested::class]);
        Queue::fake();
        Mail::fake();

        $channelName = 'New Channel ' . uniqid();
        $meta = [
            'new_channel' => [
                'name' => $channelName,
                'creator_name' => 'Creator',
                'email' => 'creator@example.com',
                'youtube_name' => 'yt-name',
            ],
        ];

        $approver = User::factory()->create();
        $application = ChannelApplication::factory()
            ->withMeta($meta)
            ->create();

        $useCase = $this->app->make(ApproveChannelApplication::class);
        $useCase->handle($application, $approver);

        $createdChannel = Channel::where('name', $channelName)->first();
        $this->assertNotNull($createdChannel);

        $this->assertTrue(
            $createdChannel
                ->channelUsers()
                ->wherePivot('user_id', $application->user->getKey())
                ->wherePivot('is_user_verified', true)
                ->exists()
        );

        Event::assertNotDispatched(ChannelAccessRequested::class);
    }

    public function testFailureToAssignUserRollsBackTransaction(): void
    {
        Event::fake([ChannelAccessRequested::class]);
        Queue::fake();
        Mail::fake();

        $channelName = 'Failing Channel ' . uniqid();
        $meta = [
            'new_channel' => [
                'name' => $channelName,
                'creator_name' => 'Creator',
                'email' => 'creator@example.com',
            ],
        ];

        $application = ChannelApplication::factory()
            ->withMeta($meta)
            ->create();

        $initialChannelCount = Channel::count();

        $channelRepository = $this->getMockBuilder(ChannelRepository::class)
            ->onlyMethods(['assignUserToChannel'])
            ->getMock();
        $channelRepository->expects($this->once())
            ->method('assignUserToChannel')
            ->willReturn(false);

        $this->app->instance(ChannelRepository::class, $channelRepository);
        $channelService = $this->app->make(ChannelService::class);

        $useCase = new ApproveChannelApplication($channelService, $channelRepository);

        $this->expectException(RuntimeException::class);
        $useCase->handle($application, null);

        $this->assertSame($initialChannelCount, Channel::count());
        $this->assertFalse($application->user->channels()->exists());
        Event::assertNotDispatched(ChannelAccessRequested::class);
    }
}
