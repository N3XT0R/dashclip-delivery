<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Videos\Pages\ListVideos;
use App\Filament\Admin\Resources\Videos\Pages\ViewVideo;
use App\Filament\Admin\Resources\Videos\RelationManagers\ClipsRelationManager;
use App\Models\Channel;
use App\Models\User;
use App\Models\Video;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * The channel a submitter wished for is visible in the administration, not only during the upload.
 */
final class VideoPreferredChannelTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function testTheVideoListShowsTheWishedChannel(): void
    {
        $video = $this->videoWishingFor('Wunschkanal Liste');

        Livewire::test(ListVideos::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$video])
            ->assertSee('Wunschkanal Liste');
    }

    public function testTheVideoDetailsShowTheWishedChannel(): void
    {
        $video = $this->videoWishingFor('Wunschkanal Detail');

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertSee('Wunschkanal Detail');
    }

    public function testTheClipListOfAVideoShowsTheWishedChannel(): void
    {
        $video = $this->videoWishingFor('Wunschkanal Clip');

        Livewire::test(ClipsRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => ViewVideo::class,
        ])
            ->assertStatus(200)
            ->assertSee('Wunschkanal Clip');
    }

    public function testAVideoWithoutAWishStaysReadable(): void
    {
        $video = Video::factory()->withClips(1)->create();

        Livewire::test(ListVideos::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$video]);
    }

    private function videoWishingFor(string $channelName): Video
    {
        $channel = Channel::factory()->create(['name' => $channelName]);
        $video = Video::factory()->withClips(1)->create();
        $video->clips()->first()->update(['preferred_channel_id' => $channel->getKey()]);

        return $video->refresh();
    }
}
