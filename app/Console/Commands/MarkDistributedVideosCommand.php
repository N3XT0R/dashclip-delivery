<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Video\MarkDistributedVideosDeletedUseCase;
use Illuminate\Console\Command;

class MarkDistributedVideosCommand extends Command
{
    protected $signature = 'videos:mark-distributed {--dry-run : Only count the videos that would be marked}';

    protected $description = 'Mark videos as deleted once the distribution will never offer them again';

    public function handle(MarkDistributedVideosDeletedUseCase $markDistributedVideos): int
    {
        $dryRun = (bool)$this->option('dry-run');
        $result = $markDistributedVideos->handle($dryRun);

        $this->info($dryRun
            ? "Would mark {$result->marked} distributed video(s) as deleted."
            : "Marked {$result->marked} distributed video(s) as deleted.");

        return self::SUCCESS;
    }
}
