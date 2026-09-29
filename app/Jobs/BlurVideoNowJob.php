<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Application\Video\BlurVideoNowUseCase;
use App\Exceptions\Censor\VideoCensorException;
use App\Models\User;
use App\Models\Video;
use App\Repository\VideoRepository;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Blurs the number plates of one video in the background, because it takes minutes rather than
 * seconds. The person who asked for it is told either way.
 */
final class BlurVideoNowJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 7200;

    public function __construct(
        public readonly int $videoId,
        public readonly ?int $actorId = null,
    ) {
    }

    public function uniqueId(): string
    {
        return sprintf('%s:blur_video_%d', app()->environment(), $this->videoId);
    }

    public function handle(BlurVideoNowUseCase $blurVideo, VideoRepository $videos): void
    {
        $video = $videos->findById($this->videoId);
        if ($video === null) {
            return;
        }

        $actor = $this->actorId === null ? null : User::query()->find($this->actorId);

        try {
            $blurVideo->handle($video, $actor);
        } catch (VideoCensorException $exception) {
            Log::warning('Blurring on request failed', [
                'video_id' => $this->videoId,
                'message' => $exception->getMessage(),
            ]);

            $this->tell($actor, $video, succeeded: false);

            return;
        }

        $this->tell($actor, $video, succeeded: true);
    }

    private function tell(?User $actor, Video $video, bool $succeeded): void
    {
        if ($actor === null) {
            return;
        }

        $name = (string)$video->getAttribute('original_name');
        $notification = Notification::make()
            ->title(__($succeeded ? 'video_versions.blur_done.title' : 'video_versions.blur_failed.title'))
            ->body(__(
                $succeeded ? 'video_versions.blur_done.body' : 'video_versions.blur_failed.body',
                ['video' => $name]
            ))
            ->icon(Heroicon::OutlinedShieldCheck);

        ($succeeded ? $notification->success() : $notification->danger())->sendToDatabase($actor);
    }
}
