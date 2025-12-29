<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Video;

use App\Application\Video\AssignVideoToTeam;
use App\Models\Clip;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use Tests\DatabaseTestCase;

final class AssignVideoToTeamTest extends DatabaseTestCase
{
    public function testAssignsTeamFromFirstClipsOwner(): void
    {
        $user = User::factory()->create();
        $this->createOwnedTeam($user);
        $video = Video::factory()->create();
        Clip::factory()->for($video, 'video')->forUser($user)->create();

        $useCase = $this->app->make(AssignVideoToTeam::class);
        $useCase->handle($video);

        $video->refresh()->load('team.owner');
        $this->assertNotNull($video->team);
        $this->assertTrue($video->team->owner->is($user));
    }

    public function testDoesNotAssignTeamWhenVideoHasNoClips(): void
    {
        $video = Video::factory()->create();

        $useCase = $this->app->make(AssignVideoToTeam::class);
        $useCase->handle($video);

        $video->refresh();
        $this->assertNull($video->team);
    }

    public function testDoesNotAssignTeamWhenClipOwnerHasNoOwnTeam(): void
    {
        $clipOwner = User::factory()->create();
        $clipOwner->teams()->detach();
        Team::query()->where('owner_id', $clipOwner->getKey())->delete();
        $video = Video::factory()->create();
        Clip::factory()->for($video, 'video')->forUser($clipOwner)->create();

        $useCase = $this->app->make(AssignVideoToTeam::class);
        $useCase->handle($video);

        $video->refresh();
        $this->assertNull($clipOwner->ownTeams()->first());
        $this->assertNull($video->team);
    }

    public function testUsesFirstClipEvenWhenSubsequentClipsHaveOwners(): void
    {
        $firstClipOwner = User::factory()->create();
        $this->createOwnedTeam($firstClipOwner);
        $secondClipOwner = User::factory()->create();
        $this->createOwnedTeam($secondClipOwner);

        $video = Video::factory()->create();
        Clip::factory()->for($video, 'video')->forUser($firstClipOwner)->create();
        Clip::factory()->for($video, 'video')->forUser($secondClipOwner)->create();

        $useCase = $this->app->make(AssignVideoToTeam::class);
        $useCase->handle($video);

        $video->refresh()->load('team.owner');
        $this->assertNotNull($video->team);
        $this->assertTrue($video->team->owner->is($firstClipOwner));
        $this->assertFalse($video->team->owner->is($secondClipOwner));
    }

    private function createOwnedTeam(User $owner): Team
    {
        $team = Team::factory()->forUser($owner)->create();
        $team->users()->attach($owner);

        return $team;
    }
}
