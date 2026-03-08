<?php

declare(strict_types=1);

namespace App\Enum\Ingest;

enum IngestStepEnum: string
{
    case LookupAndUpdateVideoHash = 'lookup_and_update_video_hash';
    case GeneratePreviewForVideoClips = 'generate_preview_for_clips';
    case UploadVideoToDropbox = 'upload_video_to_dropbox';
}
