<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanUpDiskCommand extends Command
{
    protected $signature = 'clean:disk
    {--disk=uploads : The filesystem disk to clean (as defined in config/filesystems.php)}
    {--days=30 : Delete files older than this many days}';

    public function handle(): int
    {


        return self::SUCCESS;
    }
}
