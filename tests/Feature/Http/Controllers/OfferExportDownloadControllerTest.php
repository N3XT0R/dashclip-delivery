<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enum\Guard\GuardEnum;
use App\Enum\OfferExportFormatEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Exports\OfferExporter;
use App\Models\Assignment;
use App\Models\User;
use App\Services\OfferExportFileService;
use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\DatabaseTestCase;

final class OfferExportDownloadControllerTest extends DatabaseTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->owner = User::factory()->standard()->create();
    }

    private function export(array $attributes = []): Export
    {
        $export = new Export();
        $export->forceFill([
            'exporter' => OfferExporter::class,
            'file_disk' => 'local',
            'file_name' => 'videos_test',
            'total_rows' => 2,
            'processed_rows' => 2,
            'successful_rows' => 1,
            'completed_at' => now(),
            ...$attributes,
        ]);
        $export->user()->associate($this->owner);
        $export->save();

        return $export;
    }

    private function url(Export $export): string
    {
        return URL::signedRoute('offers.exports.download', ['exportId' => $export->getKey(), 'authGuard' => GuardEnum::STANDARD->value], absolute: false);
    }

    public function testOwnerReceivesTheZipAndOnlyPackedOffersAreMarkedDownloaded(): void
    {
        $packed = Assignment::factory()->create(['status' => 'notified']);
        $skipped = Assignment::factory()->create(['status' => 'notified']);
        $export = $this->export();
        $files = $this->app->make(OfferExportFileService::class);
        Storage::disk('local')->put($files->zipPath($export), 'zip-bytes');
        $files->writePackedIds($export, [$packed->id]);

        $response = $this->actingAs($this->owner, GuardEnum::STANDARD->value)->get($this->url($export))->assertOk();

        $this->assertSame('attachment; filename=videos_test.zip', $response->headers->get('content-disposition'));
        $this->assertDatabaseHas('assignments', ['id' => $packed->id, 'status' => 'picked_up']);
        $this->assertDatabaseHas('assignments', ['id' => $skipped->id, 'status' => 'notified']);
        $this->actingAs($this->owner, GuardEnum::STANDARD->value)->get($this->url($export))->assertOk();
    }

    public function testOtherUsersAndGuestsAreRejected(): void
    {
        $export = $this->export();
        Storage::disk('local')->put($this->app->make(OfferExportFileService::class)->zipPath($export), 'zip-bytes');

        $this->get($this->url($export))
            ->assertRedirect(Filament::getPanel(PanelEnum::STANDARD->value)->getLoginUrl());
        $this->assertSame(url($this->url($export)), session('url.intended'));
        $this->actingAs(User::factory()->standard()->create(), GuardEnum::STANDARD->value)
            ->get($this->url($export))->assertForbidden();
        $this->actingAs($this->owner, GuardEnum::STANDARD->value)
            ->get('/offers/exports/'.$export->id.'/download?authGuard=standard')->assertForbidden();
    }

    public function testExpiredDownloadsExplainThemselvesInsteadOfShowingAnError(): void
    {
        $export = $this->export();

        $this->actingAs($this->owner, GuardEnum::STANDARD->value)->get($this->url($export))
            ->assertStatus(410)
            ->assertSee(__('my_offers.export.gone.title'))
            ->assertSee(__('my_offers.export.gone.body'));
    }

    public function testDeletedExportsExplainThemselvesAsWell(): void
    {
        $export = $this->export();
        $url = $this->url($export);
        $export->delete();

        $this->actingAs($this->owner, GuardEnum::STANDARD->value)->get($url)
            ->assertStatus(410)
            ->assertSee(__('my_offers.export.gone.title'));
    }

    public function testForeignUnfinishedOrMissingExportsAreNotFound(): void
    {
        $foreign = $this->export(['exporter' => 'App\\Other\\Exporter']);
        $unfinished = $this->export(['completed_at' => null]);
        $files = $this->app->make(OfferExportFileService::class);
        Storage::disk('local')->put($files->zipPath($foreign), 'zip-bytes');
        Storage::disk('local')->put($files->zipPath($unfinished), 'zip-bytes');

        foreach ([$foreign, $unfinished] as $export) {
            $this->actingAs($this->owner, GuardEnum::STANDARD->value)->get($this->url($export))->assertNotFound();
        }
    }

    public function testNotificationButtonPointsToTheSignedDownload(): void
    {
        $export = $this->export();

        $action = OfferExportFormatEnum::Zip->getDownloadNotificationAction($export, GuardEnum::STANDARD->value);

        $this->assertSame(__('my_offers.export.download'), $action->getLabel());
        $this->assertSame($this->url($export), $action->getUrl());
    }
}
