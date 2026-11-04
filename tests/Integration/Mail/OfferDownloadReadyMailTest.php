<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Enum\Guard\GuardEnum;
use App\Filament\Standard\Exports\OfferExporter;
use App\Mail\OfferDownloadReadyMail;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\User;
use App\Services\OfferExportFileService;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class OfferDownloadReadyMailTest extends DatabaseTestCase
{
    private User $operator;

    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->channel = Channel::factory()->create(['name' => 'Road Rave']);
        $this->operator = User::factory()->standard()->haveAccessToChannel($this->channel)->create();
    }

    public function testMailNamesTheChannelAndTheNumberOfReadyAndSkippedVideos(): void
    {
        $mail = new OfferDownloadReadyMail($this->export(total: 3, successful: 2), GuardEnum::STANDARD->value, $this->channel);

        $html = $mail->render();

        self::assertSame(__('mails.offer_download_ready.subject'), $mail->envelope()->subject);
        self::assertStringContainsString('Road Rave', $html);
        self::assertStringContainsString(
            e(trans_choice('mails.offer_download_ready.ready', 2, ['count' => 2, 'channel' => 'Road Rave'])),
            $html,
        );
        self::assertStringContainsString(e(trans_choice('mails.offer_download_ready.skipped', 1, ['count' => 1])), $html);
    }

    public function testMailLeavesOutTheSkippedLineWhenEverythingWasPacked(): void
    {
        $html = (new OfferDownloadReadyMail($this->export(total: 2, successful: 2), GuardEnum::STANDARD->value, $this->channel))->render();

        self::assertStringNotContainsString(e(trans_choice('mails.offer_download_ready.skipped', 1, ['count' => 1])), $html);
    }

    public function testDownloadLinkIsAbsoluteAndDeliversTheZip(): void
    {
        $export = $this->export(total: 1, successful: 1);
        $files = $this->app->make(OfferExportFileService::class);
        Storage::disk('local')->put($files->zipPath($export), 'zip-bytes');
        $files->writePackedIds($export, [Assignment::factory()->create(['status' => 'notified'])->id]);

        $url = (new OfferDownloadReadyMail($export, GuardEnum::STANDARD->value, $this->channel))->downloadUrl();

        self::assertStringStartsWith(rtrim((string)config('app.url'), '/') . '/offers/exports/' . $export->id . '/download', $url);
        self::assertStringContainsString('signature=', $url);
        $this->actingAs($this->operator, GuardEnum::STANDARD->value)
            ->get($url)
            ->assertOk();
    }

    private function export(int $total, int $successful): Export
    {
        $export = new Export();
        $export->forceFill([
            'exporter' => OfferExporter::class,
            'file_disk' => 'local',
            'file_name' => 'videos_test',
            'total_rows' => $total,
            'processed_rows' => $total,
            'successful_rows' => $successful,
            'completed_at' => now(),
        ]);
        $export->user()->associate($this->operator);
        $export->save();

        return $export;
    }
}
