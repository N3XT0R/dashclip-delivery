<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Notification;
use Tests\DatabaseTestCase;

final class NotificationTest extends DatabaseTestCase
{
    public function testBelongsToChannelAndHasAssignments(): void
    {
        $channel = Channel::factory()->create();

        $notification = Notification::factory()
            ->state(['channel_id' => $channel->getKey()])
            ->has(Assignment::factory()->withBatch()->count(2))
            ->create();

        $this->assertTrue($notification->channel->is($channel));
        $this->assertCount(2, $notification->assignments);
    }
}
