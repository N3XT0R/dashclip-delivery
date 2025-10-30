<?php

namespace App\Jobs;

use App\Models\Batch;
use App\Models\Channel;
use App\Services\{AssignmentService, Zip\ZipService};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BuildZipJob implements ShouldQueue
{
    use Queueable, SerializesModels, Dispatchable, InteractsWithQueue;

    /** Max attempts before the job is marked as failed */
    public int $tries = 1;            // set to 1 if you don't want auto-retries

    public int $timeout = 1200;       // 20 minutes for big ZIPs

    public function __construct(
        private readonly int $batchId,
        private readonly int $channelId,
        private readonly array $assignmentIds,
        private readonly string $ip,
        private readonly ?string $userAgent,
    ) {
    }

    public function handle(AssignmentService $assignments, ZipService $svc): void
    {
        $batch = Batch::query()->whereKey($this->batchId)->firstOrFail();
        $channel = Channel::query()->whereKey($this->channelId)->firstOrFail();
        $items = $assignments->fetchForZip($batch, $channel, collect($this->assignmentIds));
        $svc->build($batch, $channel, $items, $this->ip, $this->userAgent ?? '');
    }
}
