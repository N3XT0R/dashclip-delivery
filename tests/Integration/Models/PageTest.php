<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Page;
use Tests\DatabaseTestCase;

final class PageTest extends DatabaseTestCase
{
    public function testCanPersistPage(): void
    {
        $page = Page::factory()->create([
            'slug' => 'about',
            'title' => 'About Us',
            'section' => 'info',
            'content' => 'Sample content.',
        ]);

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'slug' => 'about',
        ]);
    }
}
