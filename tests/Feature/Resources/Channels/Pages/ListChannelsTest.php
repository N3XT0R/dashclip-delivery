<?php

declare(strict_types=1);

namespace Tests\Feature\Resources\Channels\Pages;

use App\Filament\Resources\Channels\Pages\ListChannels;
use App\Models\Channel;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class ListChannelsTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin, 'web');
    }

    public function testListChannelsDisplaysColumnsAndRecords(): void
    {
        $firstChannel = Channel::factory()->create([
            'name' => 'Alpha Channel',
            'creator_name' => 'Creator One',
            'email' => 'creator1@example.com',
            'youtube_name' => 'alpha-yt',
        ]);

        $secondChannel = Channel::factory()->create([
            'name' => 'Beta Channel',
            'creator_name' => 'Creator Two',
            'email' => 'creator2@example.com',
            'youtube_name' => 'beta-yt',
        ]);

        Livewire::test(ListChannels::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$firstChannel, $secondChannel])
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('creator_name')
            ->assertTableColumnExists('email')
            ->assertTableColumnExists('youtube_name')
            ->assertTableColumnExists('weight')
            ->assertTableColumnExists('weekly_quota')
            ->assertTableColumnExists('created_at')
            ->assertTableColumnExists('updated_at')
            ->assertTableColumnExists('is_video_reception_paused');
    }
}
