<?php

namespace Tests\Integration\Services;

use App\Models\{Assignment, Clip, Video};
use App\Services\AssignmentDistributor;
use Tests\DatabaseTestCase;

class AssignmentDistributorBundleTest extends DatabaseTestCase
{

    protected AssignmentDistributor $assignmentDistributor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assignmentDistributor = $this->app->make(AssignmentDistributor::class);
    }

    public function testBundleVideosAreAssignedTogether(): void
    {
        $v1 = Video::create(['hash' => 'h1', 'path' => 'p1']);
        $v2 = Video::create(['hash' => 'h2', 'path' => 'p2']);
        Clip::create(['video_id' => $v1->id, 'bundle_key' => 'B']);
        Clip::create(['video_id' => $v2->id, 'bundle_key' => 'B']);

        $result = $this->assignmentDistributor->distribute();

        $this->assertSame(2, $result['assigned']);
        $assignments = Assignment::query()->whereIn('video_id', [$v1->id, $v2->id])->get();
        $this->assertCount(2, $assignments);
        $this->assertSame(1, $assignments->pluck('channel_id')->unique()->count());
    }

    public function testDistributorHandlesInitialRunWithoutPreviousBatch(): void
    {
        $video = Video::create(['hash' => 'h1', 'path' => 'p1']);

        $result = $this->assignmentDistributor->distribute();

        $this->assertSame(['assigned' => 1, 'skipped' => 0], $result);
        $this->assertDatabaseHas('assignments', ['video_id' => $video->id]);
    }
}
