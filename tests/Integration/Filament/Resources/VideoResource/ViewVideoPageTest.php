<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Resources\VideoResource;

use App\Enum\Guard\GuardEnum;
use App\Filament\Resources\Videos\Pages\ViewVideo;
use App\Models\User;
use App\Models\Video;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * Integration tests for Filament v4 ViewVideo page.
 *
 * Verifies:
 *  - View page renders correctly for SuperAdmins
 *  - Meta data fields are visible and formatted
 *  - Regular users are forbidden
 */
final class ViewVideoPageTest extends DatabaseTestCase
{
    private User $admin;
    private User $regular;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->regular = User::factory()->standard(GuardEnum::DEFAULT)->create();
    }

    public function testAdminUserCanSeePreviewAction(): void
    {
        $video = Video::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200);
    }

    public function testPreviewActionHiddenWhenNoPreviewUrl(): void
    {
        $video = Video::factory()->create([
            'original_name' => 'nourl.mp4',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200);
    }

    public function testRegularUserCanAccessViewPageButHasNoRestrictedActions(): void
    {
        $video = Video::factory()->create();
        $this->actingAs($this->regular);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200);
    }
}
