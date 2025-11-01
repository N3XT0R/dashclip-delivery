<?php

use App\Facades\Cfg;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (!Cfg::has('ffmpeg_crf', 'ffmpeg')) {
            throw new RuntimeException(
                'Kategorie "ffmpeg" nicht gefunden – bitte zuerst die Initialmigration ausführen.'
            );
        }

        Cfg::set('ffmpeg_preset', 'medium', 'ffmpeg', 'string', true);
        Cfg::set('ffmpeg_crf', 30, 'ffmpeg', 'int', true);
        Cfg::set('ffmpeg_video_codec', 'libx264', 'ffmpeg', 'string', true);
        Cfg::set('ffmpeg_audio_codec', 'aac', 'ffmpeg', 'string', true);

        $videoArgs = [
            '-movflags' => '+faststart',
            '-pix_fmt' => 'yuv420p',
            '-vf' => 'scale=iw/2:-1',
        ];

        Cfg::set('ffmpeg_video_args', json_encode($videoArgs, JSON_UNESCAPED_SLASHES), 'ffmpeg', 'json', true);

        $bin = Cfg::get('ffmpeg_bin', 'ffmpeg', null);
        if (empty($bin)) {
            // /usr/bin/ffmpeg ist in Ubuntu und GitHub Actions immer vorhanden
            Cfg::set('ffmpeg_bin', '/usr/bin/ffmpeg', 'ffmpeg', 'string', true);
        }
    }

    public function down(): void
    {
        Cfg::set('ffmpeg_preset', 'veryfast', 'ffmpeg', 'string', true);
        Cfg::set('ffmpeg_crf', 28, 'ffmpeg', 'int', true);
        Cfg::set('ffmpeg_video_codec', 'libx264', 'ffmpeg', 'string', true);
        Cfg::set('ffmpeg_audio_codec', 'aac', 'ffmpeg', 'string', true);
        Cfg::set('ffmpeg_video_args', [], 'ffmpeg', 'json', true);
    }
};
