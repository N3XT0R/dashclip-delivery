<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Channel\Application;

use App\Application\Channel\Application\CreateChannelApplication;
use App\Enum\Channel\ApplicationEnum;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use DomainException;
use Tests\DatabaseTestCase;

final class CreateChannelApplicationTest extends DatabaseTestCase
{
    public function testCreatesChannelApplicationForExistingChannel(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();

        $this->assertDatabaseCount(ChannelApplication::class, 0);

        $useCase = $this->app->make(CreateChannelApplication::class);
        $useCase->handle([
            'channel_id' => $channel->getKey(),
            'note' => 'Please approve me',
        ], $user);

        $this->assertDatabaseHas(ChannelApplication::class, [
            'user_id' => $user->getKey(),
            'channel_id' => $channel->getKey(),
            'note' => 'Please approve me',
            'status' => ApplicationEnum::PENDING->value,
        ]);

        $application = ChannelApplication::first();
        $this->assertTrue($application->meta->tosAccepted);
        $this->assertNotNull($application->meta->tosAcceptedAt);
    }

    public function testFailsWhenPendingApplicationExistsForSameChannel(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();

        ChannelApplication::factory()
            ->forExistingChannel($channel)
            ->create(['user_id' => $user->getKey()]);

        $useCase = $this->app->make(CreateChannelApplication::class);

        $this->expectException(DomainException::class);

        $useCase->handle([
            'channel_id' => $channel->getKey(),
            'note' => 'Second try',
        ], $user);

        $this->assertDatabaseCount(ChannelApplication::class, 1);
    }

    public function testCreatesNewChannelRequestWhenOtherChannelFlagIsSet(): void
    {
        $user = User::factory()->create();

        $useCase = $this->app->make(CreateChannelApplication::class);
        $useCase->handle([
            'note' => 'New channel idea',
            'other_channel_request' => true,
            'new_channel_name' => 'Awesome Channel',
            'new_channel_creator_name' => 'Creator Name',
            'new_channel_email' => 'creator@example.com',
            'new_channel_youtube_name' => 'youtube-handle',
        ], $user);

        $application = ChannelApplication::first();

        $this->assertDatabaseHas(ChannelApplication::class, [
            'user_id' => $user->getKey(),
            'channel_id' => null,
            'status' => ApplicationEnum::PENDING->value,
        ]);

        $this->assertSame('New channel idea', $application->note);
        $this->assertTrue($application->meta->tosAccepted);
        $this->assertSame('Awesome Channel', $application->meta->channel['name']);
        $this->assertSame('Creator Name', $application->meta->channel['creator_name']);
        $this->assertSame('creator@example.com', $application->meta->channel['email']);
        $this->assertSame('youtube-handle', $application->meta->channel['youtube_name']);
    }
}
