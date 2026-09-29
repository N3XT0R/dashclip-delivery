<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use App\Models\Video;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Something happened to a video, optionally caused by someone.
 */
abstract class AbstractVideoEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Video $video,
        public ?User $user = null,
    ) {
    }
}
