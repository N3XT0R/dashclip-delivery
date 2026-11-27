<?php

declare(strict_types=1);

return [
    /*
     * Whether the installation can blur videos at all. Without the tooling on the machine this
     * stays off and the setting is never acted upon.
     */
    'enabled' => env('CENSOR_ENABLED', false),

    'python' => env('CENSOR_PYTHON', '/opt/censor/venv/bin/python'),
    'script' => base_path('resources/scripts/censor_video.py'),
    'model' => env('CENSOR_MODEL', '/opt/censor/plate.onnx'),

    /* Used only to import existing installation values into the database during migration. */
    'initial_settings' => [
        'columns' => 3,
        'rows' => 2,
        'frame_step' => (int)env('CENSOR_FRAME_STEP', 3),
        'confidence' => (float)env('CENSOR_CONFIDENCE', 0.15),
        'margin' => (float)env('CENSOR_MARGIN', 0.25),
        'timeout_seconds' => (int)env('CENSOR_TIMEOUT', 3600),
        'threads' => (int)env('CENSOR_THREADS', 2),
        'search_from' => (float)env('CENSOR_SEARCH_FROM', 0.0),
    ],
];
