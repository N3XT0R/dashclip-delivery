<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Resources\VideoResource;

use App\Filament\Resources\Videos\Pages\EditVideo;
use App\Models\User;
use App\Models\Video;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class EditVideoPageTest extends DatabaseTestCase
{
    private User $admin;
    private User $regular;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->regular = User::factory()->standard()->create();
    }

    public function testEditVideoPageRendersForAdminAndShowsDeleteAction(): void
    {
        $video = Video::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(EditVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertActionVisible('delete');
    }

    public function testRegularUserCannotAccessEditVideoPage(): void
    {
        $video = Video::factory()->create();

        $this->actingAs($this->regular);

        Livewire::test(EditVideo::class, ['record' => $video->getKey()])
            ->assertForbidden();
    }
}
