<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Channel\Application;

use App\Application\Channel\Application\ApproveChannelApplication;
use App\Events\Channel\ChannelAccessRequested;
use App\Facades\Activity;
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
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureActivityAlias();
    }

    public function testApprovingExistingChannelApplicationAssignsUserAndDispatchesEvent(): void
    {
        Event::fake([ChannelAccessRequested::class]);
        $this->prepareActivitySpy();
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

        Activity::shouldHaveReceived('createActivityLog')
            ->once()
            ->with(
                'channel_applications',
                $approver,
                'approved_channel_application',
                [
                    'channel_application_id' => $application->getKey(),
                    'channel_id' => $channel->getKey(),
                    'is_new_channel' => false,
                    'applicant_user_id' => $application->user->getKey(),
                ]
            );
    }

    public function testApprovingNewChannelApplicationCreatesChannelAndAssignsUser(): void
    {
        Event::fake([ChannelAccessRequested::class]);
        $this->prepareActivitySpy();
        Queue::fake();
        Mail::fake();

        $channelName = 'New Channel '.uniqid();
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

        Activity::shouldHaveReceived('createActivityLog')
            ->once()
            ->with(
                'channel_applications',
                $approver,
                'approved_channel_application',
                [
                    'channel_application_id' => $application->getKey(),
                    'channel_id' => $createdChannel->getKey(),
                    'is_new_channel' => true,
                    'applicant_user_id' => $application->user->getKey(),
                ]
            );
    }

    public function testFailureToAssignUserRollsBackTransaction(): void
    {
        Event::fake([ChannelAccessRequested::class]);
        $this->prepareActivitySpy();
        Queue::fake();
        Mail::fake();

        $channelName = 'Failing Channel '.uniqid();
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
        Activity::shouldNotHaveReceived('createActivityLog');
    }

    private function ensureActivityAlias(): void
    {
        if (!class_exists(Activity::class)) {
            class_alias(\Spatie\Activitylog\Facades\Activity::class, Activity::class);
        }
    }

    private function prepareActivitySpy(): void
    {
        Activity::spy();
        Activity::shouldReceive('withProperties')->andReturnSelf();
        Activity::shouldReceive('causedBy')->andReturnSelf();
        Activity::shouldReceive('performedOn')->andReturnSelf();
        Activity::shouldReceive('event')->andReturnSelf();
        Activity::shouldReceive('log')->andReturnSelf();
    }
}
