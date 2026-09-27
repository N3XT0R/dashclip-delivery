<?php

declare(strict_types=1);

namespace Tests\Integration\Commands;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Tests\DatabaseTestCase;

final class RestoreEarlyMarkedVideosCommandTest extends DatabaseTestCase
{
    public function testRestoresTheVideosAndReportsTheCount(): void
    {
        $video = $this->markedVideo();

        $this->artisan('videos:restore-early-marked')
            ->expectsOutputToContain('Restored 1 video(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
    }

    public function testDryRunOnlyReports(): void
    {
        $video = $this->markedVideo();

        $this->artisan('videos:restore-early-marked', ['--dry-run' => true])
            ->expectsOutputToContain('Would restore 1 video(s).')
            ->assertExitCode(0);

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
    }

    public function testSaysSoWhenNothingMatches(): void
    {
        $this->artisan('videos:restore-early-marked')
            ->expectsOutputToContain('No video was marked while one of its downloads was still running.')
            ->assertExitCode(0);
    }

    private function markedVideo(): Video
    {
        $video = Video::factory()->create(['original_name' => 'Marked too early.mp4']);
        Clip::factory()->for($video, 'video')->create();
        Assignment::factory()->forVideo($video)->forChannel(Channel::factory()->create())->create([
            'status' => StatusEnum::PICKEDUP->value,
            'expires_at' => now()->addDays(3),
        ]);
        $video->delete();
        DB::table('videos')->where('id', $video->getKey())->update(['deleted_at' => now()->toDateTimeString()]);

        return $video->refresh();
    }
}
