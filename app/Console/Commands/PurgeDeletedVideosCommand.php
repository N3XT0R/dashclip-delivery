<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Cleanup\PurgeDeletedVideosUseCase;
use Illuminate\Console\Command;

class PurgeDeletedVideosCommand extends Command
{
    protected $signature = 'videos:purge-deleted {--dry-run : Only count the videos that would be removed}';

    protected $description = 'Remove the stored files of deleted videos once the retention period has passed; their history stays';

    public function handle(PurgeDeletedVideosUseCase $purgeDeletedVideos): int
    {
        $dryRun = (bool)$this->option('dry-run');
        $result = $purgeDeletedVideos->handle($dryRun);

        if ($dryRun) {
            $this->info("Would remove the files of {$result->purged} deleted video(s).");

            return self::SUCCESS;
        }

        $this->info("Removed the files of {$result->purged} deleted video(s), {$result->failed} failed.");

        return $result->failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
