<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enum\Video\DeliveredVersionEnum;
use App\Models\Channel;
use App\Models\Video;

final class VideoVersionSwitchedMail extends AbstractLoggedMail
{
    public function __construct(
        public readonly Video $video,
        public readonly Channel $channel,
        public readonly DeliveredVersionEnum $deliveredVersion,
    ) {
        $this->subjectLine = __('mails.video_version_switched.subject', [
            'video' => (string)$video->getAttribute('original_name'),
        ]);
    }

    protected function viewName(): string
    {
        return 'emails.video-version-switched';
    }

    protected function viewData(): array
    {
        return [
            'video' => $this->video,
            'channel' => $this->channel,
            'deliveredVersion' => $this->deliveredVersion,
        ];
    }
}
