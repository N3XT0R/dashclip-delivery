<?php

declare(strict_types=1);

namespace Tests\Feature\Resources\Channels\Pages;

use App\Events\ChannelCreated;
use App\Filament\Resources\Channels\Pages\CreateChannel;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class CreateChannelTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin, 'web');
    }

    public function testCreateChannelStoresRecordAndDispatchesEvent(): void
    {
        Event::fake([ChannelCreated::class]);

        $formData = [
            'name' => 'Tech Daily',
            'creator_name' => 'Alex Example',
            'email' => 'channel@example.com',
            'youtube_name' => 'tech-daily',
            'weight' => 5,
            'weekly_quota' => 3,
            'is_video_reception_paused' => true,
        ];

        Livewire::test(CreateChannel::class)
            ->assertStatus(200)
            ->fillForm($formData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Channel::class, [
            'name' => 'Tech Daily',
            'creator_name' => 'Alex Example',
            'email' => 'channel@example.com',
            'youtube_name' => 'tech-daily',
            'weight' => 5,
            'weekly_quota' => 3,
            'is_video_reception_paused' => true,
        ]);

        Event::assertDispatched(ChannelCreated::class, function (ChannelCreated $event) {
            return $event->channel->name === 'Tech Daily';
        });
    }
}
