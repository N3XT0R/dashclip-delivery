<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Models\Activity;
use App\Models\User;
use App\Models\Video;
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
        $user = User::factory()->create();
        $video = Video::factory()->create(['original_name' => 'holiday.mp4']);

        activity()
            ->performedOn($video)
            ->causedBy($user)
            ->withProperties([
                'action' => 'upload',
                'file' => $video->original_name,
            ])
            ->log('uploaded a video');

        $this->assertDatabaseHas(Activity::class, [
            'description' => 'uploaded a video',
        ]);

        $record = Activity::latest()->firstOrFail();

        Livewire::test(ListActivities::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$record])
            ->assertTableColumnStateSet('log_name', $record->log_name, record: $record)
            ->assertTableColumnStateSet('description', $record->description, record: $record)
            ->assertTableColumnStateSet('event', $record->event, record: $record)
            ->assertTableColumnStateSet('subject', $record->subject, record: $record)
            ->assertTableColumnStateSet('causer', $record->causer, record: $record)
            ->assertTableColumnExists('properties', record: $record)
            ->assertTableColumnExists('created_at', record: $record);
    }
}
