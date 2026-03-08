<?php

declare(strict_types=1);

namespace App\Events\Video;

use App\Models\User;
use App\Models\Video;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event that is fired when a video has been stored in the storage and database, and is ready for processing.
 */
readonly class VideoStored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public function __construct(
        public Video $video,
        public ?User $user = null,
    ) {
    }
}