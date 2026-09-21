<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources\VideoResource\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Events\Video\VideoQueuedForIngest;
use App\Filament\Standard\Resources\VideoResource\Pages\CreateVideo;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Team;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;

final class CreateVideoTest extends DatabaseTestCase
{
    private User $user;

    private Team $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->withOwnTeam()
            ->admin(GuardEnum::STANDARD)
            ->create();

        $this->tenant = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->tenant, true);
        Filament::auth()->login($this->user);
        $this->actingAs($this->user, GuardEnum::STANDARD->value);

        $this->grantVideoPermissions($this->user);
    }

    public function testCreateVideoPageIsAccessible(): void
    {
        Livewire::test(CreateVideo::class)
            ->assertStatus(200);
    }

    public function testPreferredChannelSelectOffersChannelsThatAcceptVideos(): void
    {
        $active = Channel::factory()->create(['name' => 'Active Channel']);
        $paused = Channel::factory()->paused()->create(['name' => 'Paused Channel']);

        $options = $this->getPreferredChannelOptions();

        self::assertArrayHasKey($active->getKey(), $options);
        self::assertArrayNotHasKey($paused->getKey(), $options);
    }

    public function testPreferredChannelSelectIsLimitedToTheChannelsAssignedToTheTeam(): void
    {
        $assigned = Channel::factory()->create(['name' => 'Assigned Channel']);
        $foreign = Channel::factory()->create(['name' => 'Foreign Channel']);
        $this->tenant->assignedChannels()->attach($assigned->getKey(), ['quota' => 5]);

        $options = $this->getPreferredChannelOptions();

        self::assertArrayHasKey($assigned->getKey(), $options);
        self::assertArrayNotHasKey($foreign->getKey(), $options);
    }

    public function testPreferredChannelIsStoredOnTheCreatedClip(): void
    {
        Event::fake([VideoQueuedForIngest::class]);
        $channel = Channel::factory()->create(['name' => 'Wish Channel']);

        $this->createVideoThroughForm(['clip.preferred_channel_id' => (string)$channel->getKey()]);

        $clip = Clip::query()->latest('id')->firstOrFail();
        self::assertSame((int)$channel->getKey(), (int)$clip->getAttribute('preferred_channel_id'));
        self::assertSame('Wish Channel', $clip->getAttribute('preferred_channel'));
    }

    public function testUploadWithoutAPreferredChannelStoresNoPreference(): void
    {
        Event::fake([VideoQueuedForIngest::class]);
        Channel::factory()->create();

        $this->createVideoThroughForm();

        $clip = Clip::query()->latest('id')->firstOrFail();
        self::assertNull($clip->getAttribute('preferred_channel_id'));
        self::assertNull($clip->getAttribute('preferred_channel'));
    }

    public function testPreferredChannelOutOfReachIsDiscarded(): void
    {
        Event::fake([VideoQueuedForIngest::class]);
        $assigned = Channel::factory()->create(['name' => 'Assigned Channel']);
        $foreign = Channel::factory()->create(['name' => 'Foreign Channel']);
        $this->tenant->assignedChannels()->attach($assigned->getKey(), ['quota' => 5]);

        $this->createVideoThroughForm(
            ['clip.preferred_channel_id' => (string)$foreign->getKey()],
            assertNoFormErrors: false,
        );

        $this->assertDatabaseMissing(Clip::class, ['preferred_channel_id' => $foreign->getKey()]);
    }

    /**
     * @param array<string, mixed> $formData
     * @param bool $assertNoFormErrors
     * @return void
     */
    private function createVideoThroughForm(array $formData = [], bool $assertNoFormErrors = true): void
    {
        $file = UploadedFile::fake()->create('clip.mp4', 64, 'video/mp4');

        $component = Livewire::test(CreateVideo::class)
            ->set('data.file.' . str()->uuid()->toString(), $file)
            ->fillForm(array_merge([
                'clip.duration' => 30,
                'clip.start_sec' => '00:00',
                'clip.end_sec' => '00:30',
            ], $formData))
            ->call('create');

        if ($assertNoFormErrors) {
            $component->assertHasNoFormErrors();
        }
    }

    /**
     * @return array<int, string>
     */
    private function getPreferredChannelOptions(): array
    {
        return Livewire::test(CreateVideo::class)
            ->instance()
            ->getSchema('form')
            ->getComponent('clip.preferred_channel_id')
            ->getOptions();
    }

    private function grantVideoPermissions(User $user): void
    {
        $permissions = [
            'ViewAny:Video',
            'Create:Video',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, GuardEnum::STANDARD->value);
        }

        $user->givePermissionTo($permissions);
    }
}
