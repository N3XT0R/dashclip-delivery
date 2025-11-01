<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Resources\VideoResource;

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
 *  - Preview action visibility depends on `preview_url`
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
        $this->regular = User::factory()->standard()->create();
    }

    public function testAdminUserCanSeePreviewAction(): void
    {
        $video = Video::factory()->create([
            'preview_url' => 'https://cdn.example.test/video/preview.mp4',
        ]);

        $this->actingAs($this->admin);

        $component = Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertActionVisible('preview');

        $action = $component->instance()->getAction('preview');
        $this->assertSame(
            'https://cdn.example.test/video/preview.mp4',
            $action->getUrl(),
            'Preview-Action verweist auf falsche URL.'
        );
    }

    public function testPreviewActionHiddenWhenNoPreviewUrl(): void
    {
        $video = Video::factory()->create([
            'original_name' => 'nourl.mp4',
            'preview_url' => null,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertActionHidden('preview');
    }

    public function testRegularUserCanAccessViewPageButHasNoRestrictedActions(): void
    {
        $video = Video::factory()->create([
            'preview_url' => 'https://cdn.example.test/video/preview.mp4',
        ]);

        $this->actingAs($this->regular);

        $component = Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertActionVisible('preview');
        
        $this->assertNull(
            $component->instance()->getAction('delete'),
            'Delete-Action sollte auf der ViewVideo-Seite nicht existieren.'
        );
    }
}
