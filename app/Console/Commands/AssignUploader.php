<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repository\ClipRepository;
use App\Services\ClipService;
use Illuminate\Console\Command;

class AssignUploader extends Command
{
    protected $signature = 'assign:uploader';

    public function handle(ClipRepository $clipRepository, ClipService $clipService): int
    {
        $clipsWithoutUserId = $clipRepository->getClipsWhereUserIdIsNull();

        foreach ($clipsWithoutUserId as $clip) {
            $clipService->assignUploaderIfPossible($clip);
        }

        return self::SUCCESS;
    }
}