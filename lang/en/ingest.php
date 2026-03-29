<?php

return [
    'status' => [
        'heading' => 'Ingest status',
        'description' => 'Status of the video processing pipeline and its steps.',
        'progress' => 'Ingest progress',
        'progress_label' => ':completed/:total completed (:percent%)',
        'current_step' => 'Current step: :step',
        'no_active_step' => 'No active step',
        'current' => 'current',
    ],

    'step_status' => [
        'pending' => 'Pending',
        'running' => 'Running',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],

    'steps' => [
        'lookup_and_update_video_hash' => 'Calculate and update video hash',
        'generate_preview_for_clips' => 'Generate preview for clips',
        'upload_video_to_dropbox' => 'Upload video to Dropbox',
    ],
];
