<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Video;
use App\Services\DownloadCacheService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\DatabaseTestCase;
use ZipArchive;

class DownloadReliabilityTest extends DatabaseTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/downloads-'.Str::uuid());
        config()->set('filesystems.default', 'local');
        config()->set('filesystems.disks.local.root', $this->root);
        Storage::forgetDisk('local');
        config()->set('queue.default', 'sync');
        config()->set('broadcasting.default', 'nonexistent');
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->deleteDirectory('');
        parent::tearDown();
    }

    private function offer(Channel $channel, string $name = 'video.mp4'): Assignment
    {
        $path = 'videos/'.Str::uuid().'.mp4';
        Storage::put($path, 'video-content');
        return Assignment::factory()->forChannel($channel)->forVideo(Video::factory()->create([
            'disk' => 'local', 'path' => $path, 'original_name' => $name, 'bytes' => 13,
        ]))->create();
    }

    private function startUrl(Channel $channel): string
    {
        return URL::temporarySignedRoute('zips.channel.start', now()->addHour(), ['channel' => $channel->id]);
    }

    public function testSingleVideoIsDeliveredAsZipWithInfoCsv(): void
    {
        $channel = Channel::factory()->create();
        $offer = $this->offer($channel);
        $data = $this->postJson($this->startUrl($channel), [
            'assignment_ids' => [$offer->id],
        ])->assertOk()->json();

        $this->assertNotNull($data['jobId']);
        $this->assertCount(1, $data['downloads']);
        $this->getJson($data['progressUrl'])->assertOk()->assertJsonPath('status', 'ready');
        $response = $this->get($data['downloadUrl'])->assertOk();
        $archive = new ZipArchive();
        $this->assertTrue($archive->open($response->baseResponse->getFile()->getPathname()));
        $this->assertSame(2, $archive->numFiles);
        $this->assertSame('video-content', $archive->getFromName('video.mp4'));
        $csv = (string) $archive->getFromName('info.csv');
        $archive->close();
        $this->assertStringStartsWith('filename', ltrim($csv, "\xEF\xBB\xBF"));
        $this->assertStringContainsString('video.mp4', $csv);
        $this->assertDatabaseHas('assignments', ['id' => $offer->id, 'status' => 'picked_up']);
    }

    public function testSingleVideoStaysDownloadableWhenZipCannotBeQueued(): void
    {
        config()->set('queue.default', 'nonexistent');
        $channel = Channel::factory()->create();
        $offer = $this->offer($channel);
        $data = $this->postJson($this->startUrl($channel), [
            'assignment_ids' => [$offer->id],
        ])->assertOk()->assertJsonPath('status', 'failed')->json();

        $url = $data['downloads'][0]['url'];
        $first = $this->get($url)->assertOk()->assertHeader('content-length', '13');
        $this->assertSame('video-content', $first->streamedContent());
        $this->assertSame('video-content', $this->get($url)->assertOk()->streamedContent());
        $this->assertDatabaseHas('assignments', ['id' => $offer->id, 'status' => 'picked_up']);
    }

    public function testZipCanBePolledAfterCompletionAndDownloadedRepeatedlyWithDuplicateNames(): void
    {
        $channel = Channel::factory()->create();
        $first = $this->offer($channel);
        $second = $this->offer($channel);
        $data = $this->postJson($this->startUrl($channel), [
            'assignment_ids' => [$first->id, $second->id],
        ])->assertOk()->json();
        $this->getJson($data['progressUrl'])->assertOk()->assertJsonPath('status', 'ready');
        $response = $this->get($data['downloadUrl'])->assertOk();
        $archive = new ZipArchive();
        $this->assertTrue($archive->open($response->baseResponse->getFile()->getPathname()));
        $this->assertSame('video-content', $archive->getFromName('video.mp4'));
        $this->assertSame('video-content', $archive->getFromName($second->id.'_video.mp4'));
        $archive->close();
        ob_start();
        $response->baseResponse->sendContent();
        ob_end_clean();
        $this->get($data['downloadUrl'])->assertOk();
        $this->get($data['downloadUrl'], ['Range' => 'bytes=0-9'])->assertStatus(206);
    }

    public function testFailedZipKeepsWorkingIndividualDownloads(): void
    {
        $channel = Channel::factory()->create();
        $good = $this->offer($channel);
        $missing = $this->offer($channel);
        Storage::delete($missing->video->path);
        $data = $this->postJson($this->startUrl($channel), [
            'assignment_ids' => [$good->id, $missing->id],
        ])->assertOk()->json();
        $this->getJson($data['progressUrl'])->assertOk()->assertJsonPath('status', 'failed');
        $this->get($data['downloadUrl'])->assertNotFound();
        $this->assertSame('video-content', $this->get($data['downloads'][0]['url'])->assertOk()->streamedContent());
        $this->get($data['downloads'][1]['url'])->assertNotFound();
    }

    public function testQueueFailureStillReturnsIndividualLinks(): void
    {
        config()->set('queue.default', 'nonexistent');
        $channel = Channel::factory()->create();
        $offer = $this->offer($channel);
        $response = $this->postJson($this->startUrl($channel), ['assignment_ids' => [$offer->id]])
            ->assertOk()->assertJsonPath('status', 'failed');
        $this->assertSame('video-content', $this->get($response->json('downloads.0.url'))->assertOk()->streamedContent());
    }

    public function testQueuedFailurePublishesStatusAndConcurrentRequestsHaveDifferentJobs(): void
    {
        config()->set('queue.default', 'database');
        $channel = Channel::factory()->create();
        $offer = $this->offer($channel);
        Storage::delete($offer->video->path);
        $first = $this->postJson($this->startUrl($channel), ['assignment_ids' => [$offer->id]])->assertOk()->json();
        $second = $this->postJson($this->startUrl($channel), ['assignment_ids' => [$offer->id]])->assertOk()->json();
        $this->assertNotSame($first['jobId'], $second['jobId']);
        $this->artisan('queue:work', ['--once' => true, '--tries' => 1])->assertExitCode(0);
        $this->getJson($first['progressUrl'])->assertOk()->assertJsonPath('status', 'failed');
        $this->getJson($second['progressUrl'])->assertOk()->assertJsonPath('status', 'queued');
        $this->assertNotNull(Queue::connection()->pop());
        $this->travel(21)->minutes();
        $this->assertNull(Queue::connection()->pop());
    }

    public function testCacheFailureStillReturnsIndividualLinks(): void
    {
        $channel = Channel::factory()->create();
        $offer = $this->offer($channel);
        config()->set('cache.default', 'nonexistent');
        $response = $this->postJson($this->startUrl($channel), ['assignment_ids' => [$offer->id]])
            ->assertOk()->assertJsonPath('status', 'failed');
        $this->assertSame('video-content', $this->get($response->json('downloads.0.url'))->assertOk()->streamedContent());
    }

    public function testDownloadLinksRejectTamperingAndExpiredOffers(): void
    {
        $channel = Channel::factory()->create();
        $offer = $this->offer($channel);
        $other = $this->offer(Channel::factory()->create());
        $data = $this->postJson($this->startUrl($channel), [
            'assignment_ids' => [$offer->id, $other->id],
        ])->assertOk()->json();
        $this->assertCount(1, $data['downloads']);
        $url = $data['downloads'][0]['url'];
        $this->get(str_replace('/offers/'.$offer->id.'/', '/offers/'.$other->id.'/', $url))->assertForbidden();
        $offer->update(['expires_at' => now()->subMinute()]);
        $this->get($url)->assertGone();
        $this->postJson('/zips/channel/'.$channel->id, ['assignment_ids' => [$other->id]])->assertForbidden();
        $this->get('/zips/unknown/download')->assertForbidden();
        $this->getJson('/zips/unknown/progress')->assertForbidden();
    }

    public function testStatusSurvivesLongBuildsAndOldArchivesAreCleanedUp(): void
    {
        $cache = app(DownloadCacheService::class);
        $cache->init('long-build');
        $this->travel(21)->minutes();
        $url = URL::temporarySignedRoute('zips.progress', now()->addHour(), ['id' => 'long-build']);
        $this->getJson($url)->assertOk()->assertJsonPath('status', 'queued');
        Storage::put('zips/old.zip', 'old');
        Storage::put('zips/new.zip', 'new');
        touch(Storage::path('zips/old.zip'), now()->subDays(3)->getTimestamp());
        $this->artisan('zips:clean-expired')->assertExitCode(0);
        $this->assertFalse(Storage::exists('zips/old.zip'));
        $this->assertTrue(Storage::exists('zips/new.zip'));
    }
}
