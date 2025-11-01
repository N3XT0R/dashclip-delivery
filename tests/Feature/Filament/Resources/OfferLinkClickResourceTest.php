<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\OfferLinkClickResource;
use App\Models\OfferLinkClick;
use Carbon\Carbon;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class OfferLinkClickResourceTest extends DatabaseTestCase
{
    public function testCanCreateReturnsFalse(): void
    {
        $this->assertFalse(OfferLinkClickResource::canCreate());
    }

    public function testCanEditReturnsFalse(): void
    {
        $record = OfferLinkClick::factory()->make();
        $this->assertFalse(OfferLinkClickResource::canEdit($record));
    }

    public function testCanDeleteReturnsFalse(): void
    {
        $record = OfferLinkClick::factory()->make();
        $this->assertFalse(OfferLinkClickResource::canDelete($record));
    }

    public function testGetPagesContainsOnlyIndex(): void
    {
        $pages = OfferLinkClickResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayNotHasKey('create', $pages);
        $this->assertArrayNotHasKey('edit', $pages);
    }

    public function testModelAndLabelConfiguration(): void
    {
        $this->assertSame(OfferLinkClick::class, OfferLinkClickResource::getModel());
        $this->assertSame('Offer Link Clicks', OfferLinkClickResource::getModelLabel());
    }

    public function testTableHasExpectedColumns(): void
    {
        Livewire::test(OfferLinkClickResource\Pages\ListOfferLinkClicks::class)
            ->assertTableColumnExists('id')
            ->assertTableColumnExists('user.name')
            ->assertTableColumnExists('batch.type')
            ->assertTableColumnExists('channel.name')
            ->assertTableColumnExists('clicked_at')
            ->assertTableColumnExists('user_agent');
    }

    public function testListOfferLinkClicksShowsNewestFirst(): void
    {
        $older = OfferLinkClick::factory()->create([
            'clicked_at' => Carbon::parse('2024-01-02 12:00:00'),
        ]);

        $newer = OfferLinkClick::factory()->create([
            'clicked_at' => Carbon::parse('2024-03-01 08:00:00'),
        ]);

        Livewire::test(OfferLinkClickResource\Pages\ListOfferLinkClicks::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$newer, $older])
            ->tap(function ($livewire) use ($newer, $older) {
                // Ensure the default sort order (clicked_at desc)
                $this->assertSame(
                    [$newer->getKey(), $older->getKey()],
                    $livewire->instance()->getTableRecords()->pluck('id')->all(),
                );
            });
    }
}
