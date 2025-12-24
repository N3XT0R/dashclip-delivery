<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\DTO\Channel\ApplicationMetaDto;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use Carbon\Carbon;
use Tests\DatabaseTestCase;

final class ChannelApplicationTest extends DatabaseTestCase
{
    public function testMetaIsPersistedAndReturnedAsDto(): void
    {
        $user = User::factory()->create();

        $application = ChannelApplication::create([
            'user_id' => $user->getKey(),
            'channel_id' => null,
            'status' => 'pending',
            'note' => 'Test',
            'meta' => [
                'new_channel' => ['slug' => 'demo'],
                'tos_accepted' => true,
                'tos_accepted_at' => '2025-01-01 12:00:00',
                'reject_reason' => 'Invalid',
            ],
        ]);

        $application->refresh();

        $meta = $application->meta;

        self::assertInstanceOf(ApplicationMetaDto::class, $meta);
        self::assertSame(['slug' => 'demo'], $meta->channel);
        self::assertTrue($meta->tosAccepted);
        self::assertInstanceOf(Carbon::class, $meta->tosAcceptedAt);
        self::assertSame(
            '2025-01-01 12:00:00',
            $meta->tosAcceptedAt->toDateTimeString()
        );
        self::assertSame('Invalid', $meta->rejectReason);
    }

    public function testMetaAccessorHandlesJsonFromDatabase(): void
    {
        $user = User::factory()->create();

        $application = ChannelApplication::factory()->create([
            'user_id' => $user->getKey(),
            'meta' => [
                'new_channel' => ['name' => 'From JSON'],
                'tos_accepted' => false,
            ],
        ]);

        $application = ChannelApplication::query()->findOrFail($application->getKey());

        $meta = $application->meta;

        self::assertInstanceOf(ApplicationMetaDto::class, $meta);
        self::assertSame(['name' => 'From JSON'], $meta->channel);
        self::assertFalse($meta->tosAccepted);
        self::assertNull($meta->tosAcceptedAt);
        self::assertNull($meta->rejectReason);
    }

    public function testScopeIsNewChannelReturnsOnlyApplicationsWithoutChannel(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();

        $newChannelApplication = ChannelApplication::create([
            'user_id' => $user->getKey(),
            'channel_id' => null,
            'status' => 'pending',
            'note' => 'New channel',
            'meta' => [],
        ]);

        $existingChannelApplication = ChannelApplication::create([
            'user_id' => $user->getKey(),
            'channel_id' => $channel->getKey(),
            'status' => 'pending',
            'note' => 'Existing channel',
            'meta' => [],
        ]);

        $results = ChannelApplication::query()
            ->isNewChannel()
            ->pluck('id')
            ->all();

        self::assertContains($newChannelApplication->getKey(), $results);
        self::assertNotContains($existingChannelApplication->getKey(), $results);
    }

    public function testIsNewChannelReflectsDatabaseState(): void
    {
        $user = User::factory()->create();

        $application = ChannelApplication::factory()->create([
            'user_id' => $user->getKey(),
            'channel_id' => null,
        ]);

        self::assertTrue($application->isNewChannel());

        $channel = Channel::factory()->create();

        $application->update([
            'channel_id' => $channel->getKey(),
        ]);

        $application->refresh();

        self::assertFalse($application->isNewChannel());
    }


    public function testUserAndChannelRelationshipsWork(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();

        $application = ChannelApplication::factory()->create([
            'user_id' => $user->getKey(),
            'channel_id' => $channel->getKey(),
        ]);

        self::assertSame($user->getKey(), $application->user->getKey());
        self::assertSame($channel->getKey(), $application->channel->getKey());
    }
}
