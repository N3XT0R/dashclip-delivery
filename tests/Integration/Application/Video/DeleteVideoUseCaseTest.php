<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Video;

use App\Application\Video\DeleteVideoUseCase;
use App\Enum\StatusEnum;
use App\Exceptions\Video\VideoNotDeletableException;
use App\Models\Assignment;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class DeleteVideoUseCaseTest extends DatabaseTestCase
{
    private DeleteVideoUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->useCase = app(DeleteVideoUseCase::class);
    }

    public function testSoftDeletesADeletableVideoAndKeepsItsFile(): void
    {
        $video = $this->videoWithFile();

        $this->useCase->handle($video);

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
        Storage::disk('local')->assertExists($video->path);
    }

    public function testRejectsAVideoThatGotAnActiveOfferAfterItWasCheckedAsDeletable(): void
    {
        $video = $this->videoWithFile();
        // what the list query saw when it rendered the delete button
        $video->setAttribute('available_assignments_count', 0);
        Assignment::factory()->forVideo($video)->create([
            'status' => StatusEnum::QUEUED->value,
            'expires_at' => now()->addWeek(),
        ]);

        try {
            $this->useCase->handle($video);
            self::fail('Expected the video to be rejected as not deletable.');
        } catch (VideoNotDeletableException) {
            // expected
        }

        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
    }

    public function testRejectsAVideoWithAPickedUpOffer(): void
    {
        $video = $this->videoWithFile();
        Assignment::factory()->forVideo($video)->create(['status' => StatusEnum::PICKEDUP->value]);

        $this->expectException(VideoNotDeletableException::class);

        $this->useCase->handle($video);
    }

    public function testDeletingAnAlreadyDeletedVideoChangesNothing(): void
    {
        $video = $this->videoWithFile();
        $stale = Video::query()->findOrFail($video->getKey());
        $video->delete();

        $this->useCase->handle($stale);

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
    }

    private function videoWithFile(): Video
    {
        $video = Video::factory()->create();
        Storage::disk('local')->put($video->path, 'video-bytes');

        return $video;
    }
}
