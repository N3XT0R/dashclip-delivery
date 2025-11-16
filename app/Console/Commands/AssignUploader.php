<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AssignUploader extends Command
{
    protected $signature = 'assign:uploader';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}