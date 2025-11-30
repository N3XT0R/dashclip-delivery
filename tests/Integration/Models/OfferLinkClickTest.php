<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Batch;
use App\Models\Channel;
use App\Models\OfferLinkClick;
use App\Models\User;
use Tests\DatabaseTestCase;

final class OfferLinkClickTest extends DatabaseTestCase
{
    public function testBelongsToBatchChannelAndUser(): void
    {
        $batch = Batch::factory()->create();
        $channel = Channel::factory()->create();
        $user = User::factory()->create();

        $click = OfferLinkClick::factory()->create([
            'batch_id' => $batch->getKey(),
            'channel_id' => $channel->getKey(),
            'user_id' => $user->getKey(),
            'clicked_at' => now(),
        ]);

        $this->assertTrue($click->batch->is($batch));
        $this->assertTrue($click->channel->is($channel));
        $this->assertTrue($click->user->is($user));
    }
}
