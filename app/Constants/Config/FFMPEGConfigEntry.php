<?php

declare(strict_types=1);

namespace App\Constants\Config;

final readonly class FFMPEGConfigEntry
{
    public const string BINARY = 'ffmpeg_bin';
    public const string VIDEO_CODEC = 'ffmpeg_video_codec';
    public const string AUDIO_CODEC = 'ffmpeg_audio_codec';
    public const string PRESET = 'ffmpeg_preset';
    public const string CRF = 'ffmpeg_crf';
    public const string VIDEO_ARGS = 'ffmpeg_video_args';
}
