<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs;

use App\Filament\Standard\Exports\OfferExporter;
use App\Models\Channel;
use App\Models\User;
use App\Notifications\OfferDownloadReadyNotification;
use App\Repository\UserMailConfigRepository;
use Filament\Actions\Exports\Jobs\ExportCompletion;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Notification;
use Tests\DatabaseTestCase;

final class OfferExportCompletionJobTest extends DatabaseTestCase
{
    private User $operator;

    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('broadcasting.default', 'null');
        $this->channel = Channel::factory()->create(['name' => 'Road Rave']);
        $this->operator = User::factory()->standard()->haveAccessToChannel($this->channel)->create();
    }

    public function testOperatorIsMailedTheDownloadLinkOnceVideosWerePacked(): void
    {
        Notification::fake();
        $export = $this->export(successfulRows: 2);

        $this->complete($export);

        self::assertNotNull($export->refresh()->completed_at);
        Notification::assertSentTo(
            $this->operator,
            OfferDownloadReadyNotification::class,
            fn (OfferDownloadReadyNotification $notification, array $channels): bool => $channels === ['mail']
                && $notification->export->is($export)
                && $notification->channel?->is($this->channel),
        );
    }

    public function testNoMailWhenNoVideoCouldBePacked(): void
    {
        Notification::fake();

        $this->complete($this->export(successfulRows: 0));

        Notification::assertNotSentTo($this->operator, OfferDownloadReadyNotification::class);
    }

    public function testNoMailWhenTheOperatorTurnedItOff(): void
    {
        Notification::fake();
        app(UserMailConfigRepository::class)
            ->setForUser($this->operator, OfferDownloadReadyNotification::class, false);

        $this->complete($this->export(successfulRows: 2));

        Notification::assertNotSentTo($this->operator, OfferDownloadReadyNotification::class);
    }

    public function testInAppNotificationIsStillSent(): void
    {
        $this->complete($this->export(successfulRows: 2));

        self::assertSame(1, $this->operator->notifications()->count());
    }

    private function export(int $successfulRows): Export
    {
        $export = new Export();
        $export->forceFill([
            'exporter' => OfferExporter::class,
            'file_disk' => 'local',
            'file_name' => 'videos_test',
            'total_rows' => 2,
            'processed_rows' => 2,
            'successful_rows' => $successfulRows,
        ]);
        $export->user()->associate($this->operator);
        $export->save();

        return $export;
    }

    private function complete(Export $export): void
    {
        $completion = app(ExportCompletion::class, [
            'export' => $export,
            'columnMap' => ['id' => 'ID'],
            'formats' => (new OfferExporter($export, ['id' => 'ID'], []))->getFormats(),
            'options' => ['channel_id' => $this->channel->getKey()],
            'authGuard' => 'standard',
        ]);
        $completion->connection = 'redis';
        $completion->handle();
    }
}
