<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners\Channel;

use App\Enum\TokenPurposeEnum;
use App\Events\ActionToken\ActionTokenConsumed;
use App\Listeners\Channel\HandleChannelReceptionReactivation;
use App\Models\ActionToken;
use App\Models\Channel;
use Tests\DatabaseTestCase;

final class HandleChannelReceptionReactivationTest extends DatabaseTestCase
{
    public function testReactivatesChannelWhenTokenIsConsumed(): void
    {
        $channel = Channel::factory()->create(['is_video_reception_paused' => true]);

        $token = ActionToken::factory()->create([
            'purpose'      => TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value,
            'subject_type' => $channel->getMorphClass(),
            'subject_id'   => $channel->getKey(),
        ]);

        $event = new ActionTokenConsumed($token);
        (new HandleChannelReceptionReactivation())->handle($event);

        $this->assertFalse($channel->fresh()->is_video_reception_paused);
    }

    public function testIgnoresOtherPurposes(): void
    {
        $channel = Channel::factory()->create(['is_video_reception_paused' => true]);

        $token = ActionToken::factory()->create([
            'purpose'      => TokenPurposeEnum::CHANNEL_ACTIVATION_APPROVAL->value,
            'subject_type' => $channel->getMorphClass(),
            'subject_id'   => $channel->getKey(),
        ]);

        $event = new ActionTokenConsumed($token);
        (new HandleChannelReceptionReactivation())->handle($event);

        $this->assertTrue($channel->fresh()->is_video_reception_paused);
    }

    public function testIgnoresTokenWithNonChannelSubject(): void
    {
        $channel = Channel::factory()->create(['is_video_reception_paused' => true]);

        // Token with no subject (subject_id null)
        $token = ActionToken::factory()->create([
            'purpose'      => TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value,
            'subject_type' => null,
            'subject_id'   => null,
        ]);

        $event = new ActionTokenConsumed($token);
        (new HandleChannelReceptionReactivation())->handle($event);

        $this->assertTrue($channel->fresh()->is_video_reception_paused);
    }
}
