<?php

declare(strict_types=1);
return [
    'status' => [
        'tooltip' => ':completed/:total completed (:percent%) • :current_step',
        'current_step' => 'Current step: :step',
        'no_active_step' => 'No active step',
    ],
    'steps' => [
        'lookup_and_update_video_hash' => 'Calculate and update video hash',
        'generate_preview_for_clips' => 'Generate preview for clips',
        'upload_video_to_dropbox' => 'Upload video to Dropbox',
    ],
];
