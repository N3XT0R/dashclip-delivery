<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs;

use AnourValar\EloquentSerialize\Facades\EloquentSerializeFacade;
use App\Filament\Standard\Exports\OfferExporter;
use App\Jobs\BuildOfferExportZipJob;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\User;
use App\Models\Video;
use App\Services\CsvService;
use App\Services\OfferExportFileService;
use Filament\Actions\Exports\Jobs\ExportCompletion;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\DatabaseTestCase;
use ZipArchive;

final class BuildOfferExportZipJobTest extends DatabaseTestCase
{
    private string $root;

    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/offer-exports-'.Str::uuid());
        config()->set('filesystems.default', 'local');
        config()->set('filesystems.disks.local.root', $this->root);
        Storage::forgetDisk('local');
        $this->channel = Channel::factory()->create(['name' => 'Road Rave']);
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->deleteDirectory('');
        parent::tearDown();
    }

    private function offer(string $name, bool $withFile = true, array $attributes = []): Assignment
    {
        $path = 'videos/'.Str::uuid().'.mp4';
        if ($withFile) {
            Storage::put($path, $name.'-content');
        }

        return Assignment::factory()->forChannel($this->channel)->forVideo(Video::factory()->create([
            'disk' => 'local', 'path' => $path, 'original_name' => $name, 'bytes' => 13,
        ]))->create(['status' => 'notified', ...$attributes]);
    }

    private function export(int $rows): Export
    {
        $export = new Export();
        $export->forceFill(['exporter' => OfferExporter::class, 'file_disk' => 'local', 'total_rows' => $rows]);
        $export->user()->associate(User::factory()->standard()->create());
        $export->save();
        $export->forceFill(['file_name' => 'videos_test'])->save();

        return $export;
    }

    /** @param list<Assignment> $offers */
    private function runJob(Export $export, array $offers): void
    {
        $job = new BuildOfferExportZipJob(
            export: $export,
            query: EloquentSerializeFacade::serialize(Assignment::query()),
            columnMap: ['id' => 'ID'],
            options: ['channel_id' => $this->channel->id],
            records: $offers,
        );
        $this->app->call([$job, 'handle']);
        $export->refresh();
    }

    public function testSelectedOffersArePackedWithTheirInfoCsv(): void
    {
        $first = $this->offer('first.mp4');
        $second = $this->offer('second.mp4');
        $export = $this->export(2);

        $this->runJob($export, [$first, $second]);

        $files = $this->app->make(OfferExportFileService::class);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($files->absoluteZipPath($export)) === true);
        $this->assertSame('first.mp4-content', $zip->getFromName('first.mp4'));
        $this->assertSame('second.mp4-content', $zip->getFromName('second.mp4'));
        $this->assertSame(
            $this->app->make(CsvService::class)->buildInfoCsv(Assignment::with('video.clips')->findMany([$first->id, $second->id])),
            $zip->getFromName('info.csv'),
        );
        $zip->close();
        $this->assertSame([2, 2, 2], [$export->total_rows, $export->processed_rows, $export->successful_rows]);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $files->packedIds($export));
    }

    public function testMissingAndExpiredOffersAreSkippedAndLogged(): void
    {
        Log::spy();
        $good = $this->offer('good.mp4');
        $missing = $this->offer('missing.mp4', withFile: false);
        $expired = $this->offer('expired.mp4', attributes: ['expires_at' => now()->subMinute()]);
        $export = $this->export(3);

        $this->runJob($export, [$good, $missing, $expired]);

        $this->assertSame([3, 1], [$export->total_rows, $export->successful_rows]);
        $this->assertSame([$good->id], $this->app->make(OfferExportFileService::class)->packedIds($export));
        $reasons = [];
        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context = []) use (&$reasons): bool {
            if (str_starts_with($message, 'Offered video')) {
                $reasons[$context['assignment_id']] = $context['reason'];
            }
            return true;
        });
        $this->assertSame([$missing->id => 'file_missing', $expired->id => 'offer_unavailable'], $this->sorted($reasons));
    }

    public function testNothingPackableLeavesNoArchiveAndNoDownloadButton(): void
    {
        $missing = $this->offer('missing.mp4', withFile: false);
        $export = $this->export(1);

        $this->runJob($export, [$missing]);

        $this->assertSame(0, $export->successful_rows);
        $this->assertFalse($this->app->make(OfferExportFileService::class)->hasZip($export));
    }

    public function testCompletionNotificationCarriesTheDownloadButtonAndSkippedCount(): void
    {
        config()->set('broadcasting.default', 'null');
        $good = $this->offer('good.mp4');
        $missing = $this->offer('missing.mp4', withFile: false);
        $export = $this->export(2);
        $this->runJob($export, [$good, $missing]);

        $completion = app(ExportCompletion::class, [
            'export' => $export,
            'columnMap' => ['id' => 'ID'],
            'formats' => (new OfferExporter($export, ['id' => 'ID'], []))->getFormats(),
            'options' => [],
            'authGuard' => 'standard',
        ]);
        $completion->connection = 'redis';
        $completion->handle();

        $notification = $export->user->notifications()->sole();
        $this->assertSame(__('my_offers.export.completed.title'), $notification->data['title']);
        $this->assertStringContainsString(trans_choice('my_offers.export.completed.skipped', 1, ['count' => 1]), $notification->data['body']);
        $this->assertStringContainsString('/offers/exports/'.$export->id.'/download', $notification->data['actions'][0]['url']);
    }

    public function testFailedBuildLogsEveryOfferOnce(): void
    {
        Log::spy();
        $first = $this->offer('first.mp4');
        $second = $this->offer('second.mp4');
        $export = $this->export(2);
        $job = new BuildOfferExportZipJob(
            export: $export,
            query: EloquentSerializeFacade::serialize(Assignment::query()->whereKey([$first->id, $second->id])),
            columnMap: ['id' => 'ID'],
            options: ['channel_id' => $this->channel->id],
            records: null,
        );

        $job->failed(new RuntimeException('disk full'));

        $contexts = [];
        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context = []) use (&$contexts): bool {
            $contexts[] = $context;
            return true;
        });
        $this->assertSame(['zip_failed', 'zip_failed'], array_column($contexts, 'reason'));
        $this->assertSame([$first->id, $second->id], array_column($contexts, 'assignment_id'));
    }

    /** @param array<int, string> $values @return array<int, string> */
    private function sorted(array $values): array
    {
        ksort($values);

        return $values;
    }
}
