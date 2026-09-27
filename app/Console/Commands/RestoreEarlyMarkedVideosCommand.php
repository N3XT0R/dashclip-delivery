<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Video\RestoreEarlyMarkedVideosUseCase;
use App\Enum\ProcessingStatusEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RestoreEarlyMarkedVideosCommand extends Command
{
    protected $signature = 'videos:restore-early-marked
        {--dry-run : Only list the videos that would be restored}
        {--since= : Only videos marked at or after this date, defaults to the release of the marking run}';

    protected $description = 'Restore videos that were marked as deleted while a downloaded offer was still running';

    public function handle(RestoreEarlyMarkedVideosUseCase $restoreEarlyMarkedVideos): int
    {
        $dryRun = (bool)$this->option('dry-run');
        $since = $this->option('since') === null ? null : Carbon::parse((string)$this->option('since'));

        $this->listCandidates($restoreEarlyMarkedVideos, $since);

        $result = $restoreEarlyMarkedVideos->handle($dryRun, $since);

        $this->info($dryRun
            ? "Would restore {$result->restored} video(s)."
            : "Restored {$result->restored} video(s).");

        if ($result->skipped > 0) {
            $this->warn("Skipped {$result->skipped} video(s) whose files were already removed for good.");
        }

        return self::SUCCESS;
    }

    private function listCandidates(RestoreEarlyMarkedVideosUseCase $restoreEarlyMarkedVideos, ?Carbon $since): void
    {
        $rows = [];
        foreach ($restoreEarlyMarkedVideos->candidates($since) as $video) {
            $rows[] = [
                $video->getKey(),
                (string)$video->original_name,
                (string)$video->deleted_at,
                $video->processing_status === ProcessingStatusEnum::Deleted ? 'removed' : 'stored',
            ];
        }

        if ($rows === []) {
            $this->info('No video was marked while one of its downloads was still running.');
            return;
        }

        $this->table(['ID', 'Name', 'Marked at', 'Files'], $rows);
    }
}
