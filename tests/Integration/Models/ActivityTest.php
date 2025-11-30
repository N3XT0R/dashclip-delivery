<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Activity;
use App\Models\Video;
use Tests\DatabaseTestCase;

final class ActivityTest extends DatabaseTestCase
{
    public function testLogsActivityWithApplicationModel(): void
    {
        $video = Video::factory()->create();

        config(['activitylog.activity_model' => Activity::class]);

        $activity = activity()
            ->performedOn($video)
            ->withProperties(['foo' => 'bar'])
            ->log('test');

        $this->assertInstanceOf(Activity::class, $activity);
        $this->assertSame('test', $activity->description);
        $this->assertSame($video->getKey(), $activity->subject_id);
        $this->assertSame(Video::class, $activity->subject_type);
        $this->assertSame(['foo' => 'bar'], $activity->properties->toArray());
    }
}
