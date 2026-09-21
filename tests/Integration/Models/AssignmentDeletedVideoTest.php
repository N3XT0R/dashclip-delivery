<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use Tests\DatabaseTestCase;

final class AssignmentDeletedVideoTest extends DatabaseTestCase
{
    public function testHistoryRelationStillFindsADeletedVideo(): void
    {
        $assignment = Assignment::factory()->create(['status' => StatusEnum::PICKEDUP->value]);
        $assignment->video->delete();
        $assignment->refresh();

        self::assertNull($assignment->video);
        self::assertNotNull($assignment->videoWithTrashed);
        self::assertTrue($assignment->videoWithTrashed->trashed());
    }

    public function testAvailableOffersLeaveOutDeletedVideos(): void
    {
        $visible = Assignment::factory()->create(['status' => StatusEnum::QUEUED->value, 'expires_at' => now()->addDay()]);
        $hidden = Assignment::factory()->create(['status' => StatusEnum::QUEUED->value, 'expires_at' => now()->addDay()]);
        $hidden->video->delete();

        $ids = Assignment::query()->available()->pluck('id')->all();

        self::assertContains($visible->getKey(), $ids);
        self::assertNotContains($hidden->getKey(), $ids);
    }
}
