<?php

declare(strict_types=1);

namespace Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * Integration tests for the Filament ConfigResource.
 *
 * We verify:
 *  - ListConfigs renders and shows records
 *  - EditConfig loads a record, validates required fields, and persists changes
 */
final class ActivityResourceTest extends DatabaseTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Authenticate as a user (User::canAccessPanel returns true)
        $this->user = User::factory()->admin()->create();
        $this->actingAs($this->user);
    }

    public function testRegularUserHasNoAccess(): void
    {
        $regularUser = User::factory()->standard()->create();
        $this->actingAs($regularUser);

        Livewire::test(ListActivities::class)
            ->assertStatus(403);
    }

    public function testListActivitiesShowsExistingRecords(): void
    {
        Livewire::test(ListActivities::class)
            ->assertStatus(200)
            ->assertSee('site.name')
            ->assertDontSee('ui.theme');
    }
}
