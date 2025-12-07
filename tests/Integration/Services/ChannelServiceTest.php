<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\DTO\Channel\ChannelApplicationRequestDto;
use App\Enum\Channel\ApplicationEnum;
use App\Mail\ChannelWelcomeMail;
use App\Models\Channel;
use App\Models\User;
use App\Services\ChannelService;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Tests\DatabaseTestCase;

class ChannelServiceTest extends DatabaseTestCase
{
    protected ChannelService $channelService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->channelService = $this->app->make(ChannelService::class);
    }

    public function testPrepareChannelsAndPoolBuildsExpectedStructures(): void
    {
        // Clean slate
        Channel::query()->delete();

        // Arrange
        $channelA = Channel::factory()->create([
            'weight' => 2,
            'weekly_quota' => 3,
            'is_video_reception_paused' => false,
        ]);

        $channelB = Channel::factory()->create([
            'weight' => 1,
            'weekly_quota' => 5,
            'is_video_reception_paused' => false,
        ]);

        // Act
        $dto = $this->channelService->prepareChannelsAndPool(null, 'user', 0);

        // Assert
        $this->assertCount(2, $dto->channels);
        $this->assertCount(3, $dto->rotationPool);
        $this->assertEqualsCanonicalizing([$channelA->id, $channelB->id], $dto->channels->pluck('id')->all());
        $this->assertEquals([$channelA->id => 3, $channelB->id => 5], $dto->quota);
    }

    public function testPrepareChannelsAndPoolAppliesQuotaOverride(): void
    {
        // Clean slate
        Channel::query()->delete();

        // Arrange
        $channelA = Channel::factory()->create([
            'weight' => 2,
            'weekly_quota' => 3,
            'is_video_reception_paused' => false,
        ]);

        $channelB = Channel::factory()->create([
            'weight' => 1,
            'weekly_quota' => 5,
            'is_video_reception_paused' => false,
        ]);

        $quotaOverride = 42;

        // Act
        $dto = $this->channelService->prepareChannelsAndPool($quotaOverride, 'user', 0);

        // Assert
        $this->assertCount(2, $dto->channels);
        $this->assertCount(3, $dto->rotationPool);

        $this->assertEquals([
            $channelA->id => $quotaOverride,
            $channelB->id => $quotaOverride,
        ], $dto->quota);
    }


    public function testApproveUpdatesChannelWhenTokenIsValid(): void
    {
        // Arrange
        $channel = Channel::factory()->create([
            'is_video_reception_paused' => true,
            'approved_at' => null,
        ]);

        $token = $channel->getApprovalToken();

        // Act
        $this->channelService->approve($channel, $token);

        // Assert
        $updated = $channel->fresh();

        $this->assertFalse($updated->is_video_reception_paused);
        $this->assertNotNull($updated->approved_at);
    }

    public function testApproveThrowsExceptionWhenTokenIsInvalid(): void
    {
        // Arrange
        $channel = Channel::factory()->create([
            'is_video_reception_paused' => true,
            'approved_at' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ungültiger Bestätigungslink.');

        // Act
        $this->channelService->approve($channel, 'invalid-token');
    }

    public function testGetEligibleForWelcomeMailSelectsCorrectChannels(): void
    {
        // Clean slate
        Channel::query()->delete();

        // Arrange
        $pending = Channel::factory()->count(2)->create(['approved_at' => null]);
        $approved = Channel::factory()->count(2)->create(['approved_at' => now()]);

        // Act + Assert
        $default = $this->channelService->getEligibleForWelcomeMail(null, false);
        $this->assertCount(2, $default, 'Should return only unapproved channels by default');
        $this->assertTrue($default->diff($pending)->isEmpty());

        $forced = $this->channelService->getEligibleForWelcomeMail(null, true);
        $this->assertCount(4, $forced, 'Should return all channels when --force is used');

        $byId = $this->channelService->getEligibleForWelcomeMail((string)$pending->first()->id);
        $this->assertCount(1, $byId, 'Should return one channel by ID');
        $this->assertEquals($pending->first()->id, $byId->first()->id);

        $byEmail = $this->channelService->getEligibleForWelcomeMail($approved->first()->email);
        $this->assertCount(1, $byEmail, 'Should return one channel by email');
        $this->assertEquals($approved->first()->email, $byEmail->first()->email);
    }

    public function testSendWelcomeMailsSendsExpectedMailables(): void
    {
        // Arrange
        Mail::fake();
        Channel::query()->delete();

        $channels = Channel::factory()->count(2)->create();

        // Act
        $sent = $this->channelService->sendWelcomeMails($channels);

        // Assert
        $this->assertCount(2, $sent, 'Should return all successfully sent email addresses');
        Mail::assertQueued(ChannelWelcomeMail::class, 2);
    }

    public function testSendWelcomeMailsHandlesSendFailuresGracefully(): void
    {
        // Arrange
        Mail::fake();
        Channel::query()->delete();

        $channel = Channel::factory()->create(['email' => 'failing@example.com']);

        // Force Mail::to() to throw exception
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP failure'));

        // Act
        $result = $this->channelService->sendWelcomeMails(collect([$channel]));

        // Assert
        $this->assertSame([], $result, 'Should return empty array when sending fails');
    }

    public function testApplyForAccessCreatesApplicationForExistingChannel(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();

        $dto = new ChannelApplicationRequestDto(
            channelId: $channel->id,
            note: 'Bitte freischalten',
            otherChannelRequest: false,
            newChannelName: null,
            newChannelCreatorName: null,
            newChannelEmail: null,
            newChannelYoutubeName: null
        );

        $application = $this->channelService->applyForAccess($dto, $user);

        $this->assertNotNull($application);
        $this->assertSame($user->id, $application->user_id);
        $this->assertSame($channel->id, $application->channel_id);
        $this->assertSame('Bitte freischalten', $application->note);
        $this->assertEquals('pending', $application->status);
        $this->assertTrue($application->meta['tos_accepted']);
        $this->assertNotNull($application->meta['tos_accepted_at']);
    }

    public function testApplyForAccessThrowsForDuplicateApplication(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();

        $user->channelApplications()->create([
            'channel_id' => $channel->getKey(),
            'note' => 'Test',
            'status' => ApplicationEnum::PENDING->value,
            'meta' => [],
        ]);

        $dto = new ChannelApplicationRequestDto(
            channelId: $channel->id,
            note: 'Bitte nochmal',
            otherChannelRequest: false,
            newChannelName: null,
            newChannelCreatorName: null,
            newChannelEmail: null,
            newChannelYoutubeName: null
        );

        $this->expectException(\DomainException::class);

        $this->channelService->applyForAccess($dto, $user);
    }

    public function testApplyForAccessCreatesApplicationForNewChannel(): void
    {
        $user = User::factory()->create();

        $dto = new ChannelApplicationRequestDto(
            channelId: null,
            note: 'Neuer Kanal gewünscht',
            otherChannelRequest: true,
            newChannelName: 'MegaTV',
            newChannelCreatorName: 'Max Mustermann',
            newChannelEmail: 'tv@example.org',
            newChannelYoutubeName: 'megachannel'
        );

        $application = $this->channelService->applyForAccess($dto, $user);
        dump($application->meta);

        $this->assertNotNull($application);
        $this->assertNull($application->channel_id);
        $meta = $application->meta;
        $this->assertTrue($meta['tos_accepted']);
        $this->assertNotNull($meta['tos_accepted_at']);
        $this->assertArrayHasKey('new_channel', $meta);
        $this->assertEquals('MegaTV', $meta['new_channel']['name']);
        $this->assertEquals('Max Mustermann', $meta['new_channel']['creator_name']);
        $this->assertEquals('tv@example.org', $meta['new_channel']['email']);
        $this->assertEquals('megachannel', $meta['new_channel']['youtube_name']);
    }
}