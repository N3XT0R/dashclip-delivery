<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Application\Video\UploadVideoUseCase;
use App\Events\Video\VideoQueuedForIngest;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class VideoWriteEndpointsTest extends DatabaseTestCase
{
    private function actingUser(array $scopes): User
    {
        $user = User::factory()->withOwnTeam()->standard()->create();
        Passport::actingAs($user, $scopes);

        return $user;
    }

    public function testStoreUploadsFileCreatesClipAndDispatchesIngestEvent(): void
    {
        Storage::fake('videos');
        Event::fake([VideoQueuedForIngest::class]);
        $user = $this->actingUser(['videos:write']);

        $response = $this->post('/api/v1/videos', [
            'file' => UploadedFile::fake()->create('dashcam.mp4', 2048, 'video/mp4'),
            'clip' => ['start_sec' => 5, 'end_sec' => 30],
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertHeader('Location');
        $videoId = $response->json('data.id');
        $this->assertSame('pending', $response->json('data.processing_status'));
        $this->assertDatabaseHas('videos', [
            'id' => $videoId,
            'disk' => 'videos',
            'processing_status' => 'pending',
        ]);
        $this->assertDatabaseHas('clips', [
            'video_id' => $videoId,
            'user_id' => $user->getKey(),
            'start_sec' => 5,
            'end_sec' => 30,
        ]);
        // Path is only known from the persisted row; needed for the filesystem assertion.
        Storage::disk('videos')->assertExists(Video::query()->findOrFail($videoId)->path);
        Event::assertDispatched(VideoQueuedForIngest::class);
    }

    public function testStoreRollsBackVideoAndDeletesUploadedFileWhenClipCreationFails(): void
    {
        Storage::fake('videos');
        $user = User::factory()->make(['id' => 999999999]);
        $videoCountBefore = Video::query()->count();

        try {
            app(UploadVideoUseCase::class)->handle(
                file: UploadedFile::fake()->create('dashcam.mp4', 2048, 'video/mp4'),
                startSec: 5,
                endSec: 30,
                user: $user,
            );
            $this->fail('Expected the clip insert to fail on the unsatisfiable user_id foreign key.');
        } catch (QueryException) {
            // Expected: the clip's user_id foreign key does not resolve to a persisted user.
        }

        $this->assertSame($videoCountBefore, Video::query()->count());
        Storage::disk('videos')->assertEmpty();
    }

    public function testStoreRejectsFileExceedingConfiguredSizeLimit(): void
    {
        Storage::fake('videos');
        config()->set('livewire.temporary_file_upload.rules', ['max:100']);
        $this->actingUser(['videos:write']);

        $this->postJson('/api/v1/videos', [
            'file' => UploadedFile::fake()->create('big.mp4', 200, 'video/mp4'),
            'clip' => ['start_sec' => 5, 'end_sec' => 30],
        ])->assertUnprocessable()->assertJsonValidationErrors(['file']);
    }

    public function testStoreValidatesFileAndClipTimes(): void
    {
        Storage::fake('videos');
        $this->actingUser(['videos:write']);

        $this->postJson('/api/v1/videos', ['clip' => ['start_sec' => 10, 'end_sec' => 5]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file', 'clip.end_sec']);
    }

    public function testUpdateRenamesOwnVideo(): void
    {
        $user = $this->actingUser(['videos:write']);
        $video = Video::factory()->withClips(1, $user)->create(['original_name' => 'old.mp4']);

        $this->patchJson('/api/v1/videos/' . $video->getKey(), ['original_name' => 'new.mp4'])
            ->assertOk()->assertJsonPath('data.original_name', 'new.mp4');
    }

    public function testUpdateForeignVideoReturns404(): void
    {
        $this->actingUser(['videos:write']);
        $foreign = Video::factory()->create();

        $this->patchJson('/api/v1/videos/' . $foreign->getKey(), ['original_name' => 'x.mp4'])
            ->assertNotFound();
    }

    public function testDestroyDeletesOwnVideo(): void
    {
        Storage::fake('videos');
        $user = $this->actingUser(['videos:delete']);
        $video = Video::factory()->withClips(1, $user)->create();

        $this->deleteJson('/api/v1/videos/' . $video->getKey())->assertNoContent();
    }

    public function testWriteWithReadOnlyScopeReturns403(): void
    {
        $user = $this->actingUser(['videos:read']);
        $video = Video::factory()->withClips(1, $user)->create();

        $this->patchJson('/api/v1/videos/' . $video->getKey(), ['original_name' => 'x.mp4'])
            ->assertForbidden();
    }
}
