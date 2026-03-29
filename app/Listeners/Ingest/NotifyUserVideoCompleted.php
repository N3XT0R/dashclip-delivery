<?php

declare(strict_types=1);

namespace App\Listeners\Ingest;

use App\Events\Ingest\VideoCompleted;
use App\Services\NotificationService;

readonly class NotifyUserVideoCompleted
{

    public function __construct(
        private NotificationService $notificationService,
    ) {
    }

    public function handle(VideoCompleted $event): void
    {
        if (!$event->user) {
            return;
        }

        $this->notificationService->notifyUserVideoUploadProceeded(
            $event->user,
            $event->video,
        );
    }
}
