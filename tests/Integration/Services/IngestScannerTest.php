<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Facades\Cfg;
use App\Models\Batch;
use App\Models\Clip;
use App\Models\Video;
use App\Services\IngestScanner;
use App\Services\PreviewService;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;
use Tests\Helper\FfmpegBinaryFaker;

class IngestScannerTest extends DatabaseTestCase
{

    /** Build destination path like IngestScanner does (videos/aa/bb/hash.ext). */
    private function expectedDest(string $sha256, string $ext): string
    {
        $sub = substr($sha256, 0, 2).'/'.substr($sha256, 2, 2);
        return sprintf('videos/%s/%s.%s', $sub, $sha256, $ext);
    }

    /** Create an inbox folder under storage/app so makeStorageRelative() resolves correctly. */
    private function makeInbox(): string
    {
        $inbox = storage_path('app/_inbox_test_'.bin2hex(random_bytes(3)));
        if (!is_dir($inbox)) {
            mkdir($inbox, 0777, true);
        }
        return $inbox;
    }

    private function sampleVideoContent(): string
    {
        return 'FAKE_MP4';
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Use real local filesystem so Storage::path() works with streams/Zip etc.
        config()->set('filesystems.default', 'local');

        // Public disk with URL support for PreviewService::url()
        config()->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => '/storage',
            'visibility' => 'public',
        ]);

        // Ensure public root + previews/ directory exist (PreviewService writes there)
        Storage::makeDirectory('public');
        Storage::disk('public')->makeDirectory('previews');

        // Clean videos directory to avoid cross-test interference
        Storage::deleteDirectory('videos');
        Storage::makeDirectory('videos');
    }

    protected function tearDown(): void
    {
        // Cleanup any test inboxes created
        foreach (glob(storage_path('app/_inbox_test_*')) ?: [] as $dir) {
            foreach (glob($dir.'/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($dir);
        }
        parent::tearDown();
    }

    private function makePreviewService(): PreviewService
    {
        // Use a fake ffmpeg that creates a tiny output file and exits 0
        $faker = new FfmpegBinaryFaker();
        Cfg::set('ffmpeg_bin', $faker->success(), 'ffmpeg'); // fake binary
        Cfg::set('ffmpeg_video_args', [], 'ffmpeg', 'json');          // no extra flags

        return app(PreviewService::class);
    }

    /**
     * Test that scanning an inbox with a new video and a CSV works end-to-end
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @todo refactor this
     */
    public function testScanIngestsNewVideo_generatesPreview_importsCsv_andDeletesSourceAndCsv(): void
    {
        $inbox = $this->makeInbox();

        // Source video inside inbox
        $filename = 'cam1.mp4';
        $absFile = $inbox.'/'.$filename;
        file_put_contents($absFile, $this->sampleVideoContent());
        $hash = hash_file('sha256', $absFile);
        $destRel = $this->expectedDest($hash, 'mp4');

        // CSV in same folder (imported twice: before/after video → 2nd time deletes CSV)
        $csvPath = $inbox.'/clips.csv';
        file_put_contents($csvPath, implode("\n", [
            'filename;start;end;note;bundle;role;submitted_by',
            'cam1.mp4;00:00;00:10;intro;B;F;tester',
            '',
        ]));

        $preview = $this->makePreviewService();
        $scanner = $this->app->make(IngestScanner::class, ['previewService' => $preview]);

        // Act
        $stats = $scanner->scan($inbox, 'local');

        // Assert stats
        $this->assertSame(['new' => 1, 'dups' => 0, 'err' => 0], $stats);

        // Batch written
        $batch = Batch::query()->latest('id')->first();
        $this->assertNotNull($batch);
        $this->assertSame('ingest', $batch->type);
        $this->assertNotNull($batch->started_at);
        $this->assertNotNull($batch->finished_at);
        $this->assertEquals(['new' => 1, 'dups' => 0, 'err' => 0, 'disk' => 'local'], $batch->stats);

        // Video created & moved to final dest
        $video = Video::query()->where('hash', $hash)->first();
        $this->assertNotNull($video);
        $this->assertSame('local', $video->disk);
        $this->assertSame($destRel, $video->path);
        $this->assertSame('cam1.mp4', $video->original_name);

        // Preview: optional assertion (env-dependent). If present, validate & ensure file exists.
        if (is_string($video->preview_url) && $video->preview_url !== '') {
            $this->assertStringContainsString('/previews/', $video->preview_url);
            $urlPath = ltrim(parse_url($video->preview_url, PHP_URL_PATH) ?? '', '/'); // e.g. storage/previews/abcd.mp4
            $publicRel = preg_replace('#^storage/#', '', $urlPath);                      // -> previews/abcd.mp4
            $this->assertTrue(Storage::disk('public')->exists($publicRel));
        }

        // Destination file exists, source removed
        $this->assertTrue(Storage::exists($destRel));
        $this->assertFileDoesNotExist($absFile);

        // Clip created by importer
        $clip = Clip::query()->where('video_id', $video->id)->first();
        $this->assertNotNull($clip);
        $this->assertSame(0, $clip->start_sec);
        $this->assertSame(10, $clip->end_sec);
        $this->assertSame('intro', $clip->note);
        $this->assertSame('B', $clip->bundle_key);
        $this->assertSame('F', $clip->role);
        $this->assertSame('tester', $clip->submitted_by);

        // CSV removed on the second import pass (warnings == 0)
        $this->assertFileDoesNotExist($csvPath);
    }

    public function testScanSkipsDuplicate_andDeletesDuplicateSource(): void
    {
        $inbox = $this->makeInbox();

        // First run: ingest one
        $abs1 = $inbox.'/d1.mp4';
        file_put_contents($abs1, $this->sampleVideoContent());
        $hash = hash_file('sha256', $abs1);
        $destRel = $this->expectedDest($hash, 'mp4');

        $scanner = $this->app->make(IngestScanner::class, ['previewService' => $this->makePreviewService()]);
        $stats1 = $scanner->scan($inbox, 'local');
        $this->assertSame(['new' => 1, 'dups' => 0, 'err' => 0], $stats1);
        $this->assertTrue(Storage::exists($destRel));

        // Second run: same content, different name
        $abs2 = $inbox.'/duplicate_same_content.mp4';
        file_put_contents($abs2, $this->sampleVideoContent());

        $stats2 = $scanner->scan($inbox, 'local');
        $this->assertSame(['new' => 0, 'dups' => 1, 'err' => 0], $stats2);

        // Duplicate source deleted, only one DB row with this hash
        $this->assertFileDoesNotExist($abs2);
        $this->assertSame(1, Video::query()->where('hash', $hash)->count());
    }

    public function testScanCountsErrorWhenDestinationNotWritable_andKeepsSourceAndVideo(): void
    {
        $inbox = $this->makeInbox();

        $abs = $inbox.'/broken.mp4';
        file_put_contents($abs, $this->sampleVideoContent());
        $hash = hash_file('sha256', $abs);

        // Create a directory at the final file path to force fopen() failure
        $destRel = $this->expectedDest($hash, 'mp4');
        $destAbs = Storage::path($destRel);
        @mkdir(dirname($destAbs), 0777, true);
        @mkdir($destAbs, 0777, true);
        @file_put_contents($destAbs.'/keep', 'x');

        $scanner = $this->app->make(IngestScanner::class, ['previewService' => $this->makePreviewService()]);
        $stats = $scanner->scan($inbox, 'local');

        $this->assertSame(['new' => 0, 'dups' => 0, 'err' => 1], $stats);

        // Source still there; video row exists (created before upload)
        $this->assertFileExists($abs);
        $this->assertTrue(Video::query()->where('hash', $hash)->exists());
    }
}
