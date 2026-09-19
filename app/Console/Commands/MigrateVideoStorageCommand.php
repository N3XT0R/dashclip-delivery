<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enum\VideoStorageMigrationResultEnum;
use App\Models\Video;
use App\Services\Storage\VideoStorageMigrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Copies videos from one storage disk to another and switches each video once its copy is complete.
 * Runs can be interrupted and repeated; completed copies are reused and source files are kept.
 */
class MigrateVideoStorageCommand extends Command
{
    protected $signature = 'storage:migrate-videos
        {from : Disk the videos are currently stored on, e.g. dropbox}
        {to : Disk to copy the videos to, e.g. hetzner}
        {--video=* : Only migrate these video IDs}
        {--limit= : Stop after this many videos}
        {--dry-run : Only report what would happen}';

    protected $description = 'Copy videos to another storage disk and switch them over once the copy is complete';

    public function handle(VideoStorageMigrationService $migration): int
    {
        $from = (string) $this->argument('from');
        $to = (string) $this->argument('to');
        if ($from === $to || config("filesystems.disks.$from") === null || config("filesystems.disks.$to") === null) {
            $this->error('Source and target must be two different configured disks.');

            return self::FAILURE;
        }

        $videos = Video::query()
            ->where('disk', $from)
            ->when($this->option('video') !== [], fn ($query) => $query->whereKey($this->option('video')))
            ->when($this->option('limit') !== null, fn ($query) => $query->limit((int) $this->option('limit')))
            ->orderBy('id')
            ->get();

        $dryRun = (bool) $this->option('dry-run');
        $counts = array_fill_keys(array_map(fn (VideoStorageMigrationResultEnum $case): string => $case->value, VideoStorageMigrationResultEnum::cases()), 0);
        $failed = 0;

        foreach ($videos as $video) {
            try {
                $result = $migration->migrate($video, $to, $dryRun);
                $counts[$result->value]++;
                $this->line(sprintf('#%d %s: %s', $video->getKey(), $video->path, $result->value));
            } catch (Throwable $exception) {
                $failed++;
                $this->warn(sprintf('#%d %s: failed (%s)', $video->getKey(), $video->path, $exception->getMessage()));
                Log::warning('Video storage migration failed', [
                    'video_id' => $video->getKey(),
                    'from' => $from,
                    'to' => $to,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $this->info($dryRun
            ? sprintf('%d would be copied, %d would be switched, %d failed.', $counts['would_copy'], $counts['would_switch'], $failed)
            : sprintf('%d copied, %d switched, %d failed.', $counts['copied'], $counts['switched'], $failed));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
