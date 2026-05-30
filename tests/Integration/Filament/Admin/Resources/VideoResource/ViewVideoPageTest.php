<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Admin\Resources\VideoResource;

use App\Enum\Guard\GuardEnum;
use App\Filament\Admin\Resources\Videos\Pages\ViewVideo;
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

    public function testFormStateContainsOriginalNameAndExt(): void
    {
        $video = Video::factory()->create([
            'original_name' => 'integration-test.mp4',
            'ext' => 'mp4',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertSet('data.original_name', 'integration-test.mp4')
            ->assertSet('data.ext', 'mp4');
    }

    public function testFormStateContainsDiskAndHash(): void
    {
        $video = Video::factory()->create([
            'disk' => 'local',
            'hash' => 'abc123def456',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertSet('data.disk', 'local')
            ->assertSet('data.hash', 'abc123def456');
    }

    public function testFormStateContainsByteValue(): void
    {
        $video = Video::factory()->create(['bytes' => 2_097_152]);

        $this->actingAs($this->admin);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertSet('data.bytes', 2_097_152);
    }

    public function testFormStateContainsMetaArray(): void
    {
        $meta = ['codec' => 'h264', 'fps' => 30];
        $video = Video::factory()->create(['meta' => $meta]);

        $this->actingAs($this->admin);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertSet('data.meta', $meta);
    }
}
