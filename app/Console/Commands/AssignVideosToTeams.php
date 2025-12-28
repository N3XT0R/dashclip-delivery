<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Video\AssignVideoToTeam;
use App\Repository\VideoRepository;
use Illuminate\Console\Command;

class AssignVideosToTeams extends Command
{
    protected $signature = 'assign:videos-to-teams';

    public function handle(VideoRepository $videoRepository, AssignVideoToTeam $assignVideoToTeam): int
    {
        try {
            $videos = $videoRepository->getVideosWithoutTeam();
            foreach ($videos as $video) {
                $assignVideoToTeam->handle($video);
            }
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->warn("Error assigning videos to teams: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
