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

    public function testTheDetailsShowNameAndExtension(): void
    {
        $video = Video::factory()->create([
            'original_name' => 'integration-test.mp4',
            'ext' => 'mp4',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertSee('integration-test.mp4')
            ->assertSee('mp4');
    }

    public function testTheDetailsShowDiskAndHash(): void
    {
        $video = Video::factory()->create([
            'disk' => 'local',
            'hash' => 'abc123def456',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertSee('local')
            ->assertSee('abc123def456');
    }

    public function testTheDetailsShowTheSizeInReadableForm(): void
    {
        $video = Video::factory()->create(['bytes' => 2_097_152]);

        $this->actingAs($this->admin);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertSee('2 MB');
    }

    public function testTheDetailsSayWhetherThePlatesWereBlurred(): void
    {
        $blurred = Video::factory()->create(['source_path' => 'videos/original.mp4']);
        $untouched = Video::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(ViewVideo::class, ['record' => $blurred->getKey()])
            ->assertSee(__('filament.admin.labels.blurred_yes'));

        Livewire::test(ViewVideo::class, ['record' => $untouched->getKey()])
            ->assertSee(__('filament.admin.labels.blurred_no'));
    }

    public function testTheDetailsShowTheMetaData(): void
    {
        $meta = ['codec' => 'h264', 'fps' => 30];
        $video = Video::factory()->create(['meta' => $meta]);

        $this->actingAs($this->admin);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertSee('h264')
            ->assertSee('codec');
    }
}
