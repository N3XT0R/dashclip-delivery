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

    /*
     * A plate shrinks to a few pixels when a 1440p frame is squeezed into the 640 pixel input of
     * the model, so the frame is cut into tiles and every tile is looked at on its own.
     */
    'tiles' => ['columns' => 3, 'rows' => 2],

    /* Every n-th frame is looked at; the boxes are kept for the frames in between. */
    'frame_step' => (int)env('CENSOR_FRAME_STEP', 3),

    /* Rather one sign too many than one plate too few. */
    'confidence' => (float)env('CENSOR_CONFIDENCE', 0.15),

    /* How much wider than the detected box the blur reaches, as a share of the box. */
    'margin' => (float)env('CENSOR_MARGIN', 0.25),

    'timeout_seconds' => (int)env('CENSOR_TIMEOUT', 3600),
];
