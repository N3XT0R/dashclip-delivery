<?php

declare(strict_types=1);

namespace App\Enum\Video;

/**
 * Which of the two files of a video is handed out to channels.
 */
enum DeliveredVersionEnum: string
{
    case ORIGINAL = 'original';
    case BLURRED = 'blurred';

    /**
     * The other one, which is the one a switch hands out from then on.
     */
    public function other(): self
    {
        return $this === self::ORIGINAL ? self::BLURRED : self::ORIGINAL;
    }

    public function label(): string
    {
        return __('video_versions.' . $this->value . '.label');
    }

    /**
     * How the action that hands out this version is named.
     */
    public function action(): string
    {
        return __('video_versions.' . $this->value . '.action');
    }
}
