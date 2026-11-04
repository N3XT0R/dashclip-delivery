<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Exports;

use App\Filament\Standard\Exports\OfferExporter;
use App\Models\Channel;
use Filament\Actions\Exports\Models\Export;
use Tests\DatabaseTestCase;

final class OfferExporterTest extends DatabaseTestCase
{
    public function testCompletionTextsNameReadyAndSkippedVideos(): void
    {
        $export = new Export();
        $export->forceFill(['total_rows' => 3, 'successful_rows' => 2]);

        $this->assertSame(__('my_offers.export.completed.title'), OfferExporter::getCompletedNotificationTitle($export));
        $this->assertSame(
            trans_choice('my_offers.export.completed.ready', 3, ['ready' => 2, 'total' => 3]).' '
                .trans_choice('my_offers.export.completed.skipped', 1, ['count' => 1]),
            OfferExporter::getCompletedNotificationBody($export),
        );

        $export->forceFill(['successful_rows' => 0]);
        $this->assertSame(__('my_offers.export.failed.title'), OfferExporter::getCompletedNotificationTitle($export));
        $this->assertSame(__('my_offers.export.failed.body'), OfferExporter::getCompletedNotificationBody($export));
    }

    public function testFileNameNamesTheChannelAndTheExport(): void
    {
        $channel = Channel::factory()->create(['name' => 'Export Name Test Channel']);
        $export = new Export();
        $export->forceFill(['id' => 42]);

        $exporter = new OfferExporter($export, ['id' => 'ID'], ['channel_id' => $channel->id]);

        $this->assertMatchesRegularExpression('/^videos_export-name-test-channel_\d{4}-\d{2}-\d{2}_42$/', $exporter->getFileName($export));
    }
}
