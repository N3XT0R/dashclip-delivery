<?php

use App\Facades\Cfg;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (!Cfg::has('ffmpeg_crf', 'ffmpeg')) {
            throw new RuntimeException(
                'Category "ffmpeg" not found – please run the initial migration first.'
            );
        }

        // ------------------------------------------------------------
        // Increase CRF for stronger compression.
        // CRF 33 usually gives ~4× smaller output while keeping acceptable quality.
        // ------------------------------------------------------------
        Cfg::set('ffmpeg_crf', 33, 'ffmpeg', 'int', true);

        // ------------------------------------------------------------
        // Safe downscaling filter:
        // - Halves both width and height (≈ ¼ total pixel count)
        // - Ensures iw/ih > 0 before dividing
        // - Falls back to original dimensions if invalid
        // ------------------------------------------------------------
        $safeScale = "scale=if(gte(iw\,2)\,iw/2\,iw):if(gte(ih\,2)\,ih/2\,ih)";

        $videoArgs = [
            '-movflags' => '+faststart',
            '-pix_fmt' => 'yuv420p',
            '-vf' => $safeScale,
        ];

        Cfg::set(
            'ffmpeg_video_args',
            json_encode($videoArgs, JSON_UNESCAPED_SLASHES),
            'ffmpeg',
            'json',
            true
        );
    }

    public function down(): void
    {
        // Revert to previous defaults
        Cfg::set('ffmpeg_crf', 30, 'ffmpeg', 'int', true);

        $videoArgs = [
            '-movflags' => '+faststart',
            '-pix_fmt' => 'yuv420p',
            '-vf' => 'scale=trunc(iw/2)*2:trunc(ih/2)*2',
        ];

        Cfg::set(
            'ffmpeg_video_args',
            json_encode($videoArgs, JSON_UNESCAPED_SLASHES),
            'ffmpeg',
            'json',
            true
        );
    }
};
