<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\DTO\FileInfoDto;
use App\Facades\Cfg;
use App\Jobs\ProcessUploadedVideo;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;

class UploadVideo
{
    public const string UPLOAD_DISK_CONFIG_KEY = 'uploads.disk';

    public function handle(User $user, array $clip, ?Team $team = null): void
    {
        $targetDisk = Cfg::get('default_file_system', 'default', 'dropbox');
        $file = $clip['file'] ?? '';
        $fileInfoDto = new FileInfoDto(
            $file,
            Str::afterLast($file, '/'),
            Str::afterLast($file, '.'),
            $clip['original_name'] ?? null,
        );

        ProcessUploadedVideo::dispatch(
            user: $user,
            fileInfoDto: $fileInfoDto,
            targetDisk: $targetDisk,
            sourceDisk: config(self::UPLOAD_DISK_CONFIG_KEY),
            start: (int)($clip['start_sec'] ?? 0),
            end: (int)($clip['end_sec'] ?? 0),
            submittedBy: $user?->display_name,
            note: $clip['note'] ?? null,
            bundleKey: $clip['bundle_key'] ?? null,
            role: $clip['role'] ?? null,
            team: $team
        );
    }
}
