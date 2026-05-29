<?php

declare(strict_types=1);

namespace Tests\Integration\Commands;

use App\Models\Clip;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use Tests\DatabaseTestCase;

final class AssignVideosToTeamsTest extends DatabaseTestCase
{
    public function testAssignsTeamToVideoWithoutTeam(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->forUser($user)->create();
        $team->users()->attach($user);

        $video = Video::factory()->create(['team_id' => null]);
        Clip::factory()->for($video, 'video')->forUser($user)->create();

        $this->artisan('assign:videos-to-teams')->assertExitCode(0);

        $video->refresh()->load('team.owner');
        $this->assertNotNull($video->team);
        $this->assertTrue($video->team->owner->is($user));
    }

    public function testSkipsVideosAlreadyAssignedToTeam(): void
    {
        $team = Team::factory()->create();
        $video = Video::factory()->create(['team_id' => $team->getKey()]);

        $this->artisan('assign:videos-to-teams')->assertExitCode(0);

        $this->assertSame($team->getKey(), $video->refresh()->team_id);
    }

    public function testReturnsSuccessWhenNoUnassignedVideos(): void
    {
        $this->artisan('assign:videos-to-teams')->assertExitCode(0);
    }
}
