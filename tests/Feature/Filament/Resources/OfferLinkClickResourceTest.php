<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\OfferLinkClickResource;
use App\Models\OfferLinkClick;
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
}
