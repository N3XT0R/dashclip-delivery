<?php

declare(strict_types=1);

namespace Tests\Feature\Resources\Channels\Pages;

use App\Filament\Resources\Channels\Pages\EditChannel;
use App\Models\Channel;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class EditChannelTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin, 'web');
    }

    public function testEditChannelFormLoadsAndUpdatesRecord(): void
    {
        $channel = Channel::factory()->create([
            'name' => 'Original Channel',
            'creator_name' => 'First Creator',
            'email' => 'first@example.com',
            'youtube_name' => 'original-yt',
            'weight' => 1,
            'weekly_quota' => 2,
            'is_video_reception_paused' => false,
        ]);

        Livewire::test(EditChannel::class, ['record' => $channel->getKey()])
            ->assertStatus(200)
            ->assertFormSet([
                'name' => 'Original Channel',
                'creator_name' => 'First Creator',
                'email' => 'first@example.com',
                'youtube_name' => 'original-yt',
                'weight' => 1,
                'weekly_quota' => 2,
                'is_video_reception_paused' => false,
            ])
            ->fillForm([
                'name' => 'Updated Channel',
                'creator_name' => 'New Creator',
                'email' => 'updated@example.com',
                'youtube_name' => 'updated-yt',
                'weight' => 3,
                'weekly_quota' => 5,
                'is_video_reception_paused' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $channel->refresh();

        $this->assertSame('Updated Channel', $channel->name);
        $this->assertSame('New Creator', $channel->creator_name);
        $this->assertSame('updated@example.com', $channel->email);
        $this->assertSame('updated-yt', $channel->youtube_name);
        $this->assertSame(3, $channel->weight);
        $this->assertSame(5, $channel->weekly_quota);
        $this->assertTrue($channel->is_video_reception_paused);
    }
}
