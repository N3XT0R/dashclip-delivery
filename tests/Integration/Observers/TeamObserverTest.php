<?php

declare(strict_types=1);

namespace Tests\Integration\Observers;

use App\Models\Team;
use Illuminate\Support\Str;
use Tests\DatabaseTestCase;

final class TeamObserverTest extends DatabaseTestCase
{
    public function testAssignsUuidSlugWhenMissing(): void
    {
        $team = Team::factory()->create([
            'slug' => null,
        ]);

        $this->assertNotEmpty($team->slug);
        $this->assertTrue(Str::isUuid((string)$team->slug));
    }

    public function testKeepsExistingSlugUntouched(): void
    {
        $team = Team::factory()->create([
            'slug' => 'custom-slug',
        ]);

        $this->assertSame('custom-slug', $team->slug);
    }
}
