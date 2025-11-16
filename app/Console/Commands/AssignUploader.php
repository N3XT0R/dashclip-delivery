<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repository\ClipRepository;
use App\Services\VideoService;
use Illuminate\Console\Command;

class AssignUploader extends Command
{
    protected $signature = 'assign:uploader';

    public function handle(ClipRepository $clipRepository, private VideoService $videoService): int
    {
        return self::SUCCESS;
    }
}