<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Video;
use Tests\DatabaseTestCase;

class PreferredChannelSchemaTest extends DatabaseTestCase
{
    public function testClipStoresRawAndResolvedPreferredChannel(): void
    {
        $channel = Channel::factory()->create();
        $video = Video::factory()->create();

        $clip = Clip::factory()->for($video)->create([
            'preferred_channel' => 'Highway West',
            'preferred_channel_id' => $channel->getKey(),
        ]);

        $clip->refresh();

        $this->assertSame('Highway West', $clip->preferred_channel);
        $this->assertSame($channel->getKey(), $clip->preferredChannel->getKey());
    }

    public function testAssignmentFlagsPreferredChannelAndCastsBoolean(): void
    {
        $assignment = Assignment::factory()->create([
            'via_preferred_channel' => true,
        ]);

        $assignment->refresh();

        $this->assertTrue($assignment->via_preferred_channel);
    }

    public function testAssignmentDefaultsViaPreferredChannelToFalse(): void
    {
        $assignment = Assignment::factory()->create();

        $assignment->refresh();

        $this->assertFalse($assignment->via_preferred_channel);
    }
}
