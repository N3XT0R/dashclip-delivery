<?php

declare(strict_types=1);

namespace Tests\Feature\Standard\Pages;

use App\Auth\Abilities\AccessChannelPageAbility;
use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Pages\MyOffers;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Download;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use App\Services\AssignmentService;
use App\Services\LinkService;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\DatabaseTestCase;

final class MyOffersTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('auth.defaults.guard', GuardEnum::STANDARD->value);
        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
    }

    public function testChannelOperatorCanAccessPage(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);

        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);

        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);

        Filament::setTenant($team, true);
        Filament::auth()->login($user);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        Livewire::test(MyOffers::class)
            ->assertStatus(200)
            ->assertSee(__('my_offers.title'));
    }

    public function testUserWithoutPermissionCannotAccessPage(): void
    {
        $user = User::factory()->create();

        Filament::auth()->login($user);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        Livewire::test(MyOffers::class)
            ->assertForbidden();
    }

    public function testDownloadedOffersOfDeletedVideosRenderWithoutAPreview(): void
    {
        [$channel] = $this->actingOperator();
        $uploader = User::factory()->create();
        $assignment = $this->downloadedOfferOfDeletedVideo($channel, 'Removed Clip.mp4', $uploader);

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'downloaded')
            ->assertCanSeeTableRecords([$assignment])
            ->assertSee('Removed Clip.mp4')
            ->assertSee('images/status/no_preview.jpg')
            ->assertSee($uploader->display_name)
            // its files are still stored, so it can be fetched again until the purge run
            ->assertTableActionVisible('download_again', $assignment);
    }

    public function testReturnActionIsHiddenForADownloadedOfferOfADeletedVideoThatIsStillReturnable(): void
    {
        [$channel] = $this->actingOperator();
        $assignment = Assignment::factory()->forChannel($channel)->create([
            'status' => StatusEnum::PICKEDUP->value,
            'expires_at' => now()->addDay(),
        ]);
        Download::factory()->forAssignment($assignment)->create();
        $assignment->video->delete();

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'downloaded')
            ->assertTableActionHidden('return', $assignment);
    }

    public function testDetailsOfADeletedVideoOpenWithoutAPreview(): void
    {
        [$channel] = $this->actingOperator();
        $assignment = $this->downloadedOfferOfDeletedVideo($channel, 'Removed Clip.mp4');

        $schema = (new MyOffers())->getDetailsInfolist($assignment);
        $previewSection = collect($schema->getComponents(withHidden: true))
            ->first(
                fn (mixed $component): bool => $component instanceof Section
                    && $component->getHeading() === __('my_offers.modal.preview.heading')
            );

        self::assertInstanceOf(Section::class, $previewSection);
        self::assertFalse($previewSection->isVisible());
    }

    public function testAvailableOffersOfDeletedVideosAreNotListed(): void
    {
        [$channel] = $this->actingOperator();
        $assignment = Assignment::factory()->forChannel($channel)
            ->create(['status' => StatusEnum::QUEUED->value, 'expires_at' => now()->addDay()]);
        $assignment->video->delete();

        Livewire::test(MyOffers::class)
            ->assertCanNotSeeTableRecords([$assignment]);
    }

    /**
     * @return array{0: Channel, 1: User}
     */
    private function actingOperator(): array
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);
        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);
        Filament::setTenant($team, true);
        Filament::auth()->login($user);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        return [$channel, $user];
    }

    private function downloadedOfferOfDeletedVideo(Channel $channel, string $name, ?User $uploader = null): Assignment
    {
        $assignment = Assignment::factory()->forChannel($channel)->create(['status' => StatusEnum::PICKEDUP->value]);
        $assignment->video->update(['original_name' => $name]);
        if ($uploader !== null) {
            Clip::factory()->forVideo($assignment->video)->forUser($uploader)->create();
        }
        Download::factory()->forAssignment($assignment)->create();
        $assignment->video->delete();

        return $assignment;
    }

    public function testTabsAreRendered(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);

        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);

        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);

        Filament::setTenant($team, true);
        Filament::auth()->login($user);

        Livewire::test(MyOffers::class)
            ->assertSee(__('my_offers.tabs.available'))
            ->assertSee(__('my_offers.tabs.downloaded'))
            ->assertSee(__('my_offers.tabs.expired'))
            ->assertSee(__('my_offers.tabs.returned'));
    }

    public function testNavigationBadgeShowsTotalAvailableOffersForCurrentChannel(): void
    {
        $user = User::factory()->create();
        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);

        Assignment::factory()->withBatch()->forChannel($channel)->create([
            'status' => StatusEnum::QUEUED->value,
            'expires_at' => null,
        ]);
        Assignment::factory()->withBatch()->forChannel($channel)->create([
            'status' => StatusEnum::NOTIFIED->value,
            'expires_at' => now()->addDay(),
        ]);
        Assignment::factory()->withBatch()->forChannel($channel)->create([
            'status' => StatusEnum::QUEUED->value,
            'expires_at' => now()->subDay(),
        ]);
        Assignment::factory()->withBatch()->forChannel($channel)->create([
            'status' => StatusEnum::PICKEDUP->value,
        ]);
        Assignment::factory()->withBatch()->create([
            'status' => StatusEnum::QUEUED->value,
            'expires_at' => null,
        ]);

        Filament::setTenant($team, true);
        Filament::auth()->login($user);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        self::assertSame('2', MyOffers::getNavigationBadge());
    }

    public function testZipFormAnchorIsRenderedWhenChannelExists(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);

        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);

        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);

        Filament::setTenant($team, true);
        Filament::auth()->login($user);

        Livewire::test(MyOffers::class)
            ->assertSee('zipForm'); // ID from blade view filament.standard.components.zip-form-anchor
    }

    public function testBulkDownloadDispatchesZipEvent(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);

        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);

        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);

        $assignment = Assignment::factory()
            ->withBatch()
            ->create([
                'channel_id' => $channel->getKey(),
            ]);

        Filament::setTenant($team, true);
        Filament::auth()->login($user);

        Livewire::test(MyOffers::class)
            ->call('dispatchZipDownload', [$assignment->getKey()])
            ->assertDispatched('zip-download', function (string $name, array $params) use ($assignment): bool {
                return ($params[0]['assignmentIds'] ?? null) === [$assignment->getKey()];
            });
    }

    public function testDownloadedTabShowsEachOfferOnceWithItsLatestDownload(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);
        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);
        $older = Assignment::factory()->withBatch()->create([
            'channel_id' => $channel->getKey(), 'status' => StatusEnum::PICKEDUP->value,
        ]);
        $newer = Assignment::factory()->withBatch()->create([
            'channel_id' => $channel->getKey(), 'status' => StatusEnum::PICKEDUP->value,
        ]);
        foreach (['2026-09-10 08:15:00', '2026-09-12 09:30:00', '2026-09-11 10:45:00'] as $downloadedAt) {
            Download::factory()->create(['assignment_id' => $older->getKey(), 'downloaded_at' => $downloadedAt]);
        }
        Download::factory()->create(['assignment_id' => $newer->getKey(), 'downloaded_at' => '2026-09-15 12:00:00']);
        Filament::setTenant($team, true);
        Filament::auth()->login($user);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        $page = Livewire::test(MyOffers::class, ['activeTab' => 'downloaded'])->loadTable();

        $this->assertSame([$newer->getKey(), $older->getKey()], $page->instance()->getTableRecords()->modelKeys());
        $html = $page->html();
        $this->assertSame(1, substr_count($html, '12.09.2026 09:30'));
        $this->assertSame(0, substr_count($html, '10.09.2026 08:15'));
        $this->assertSame(0, substr_count($html, '11.09.2026 10:45'));
        $this->assertSame(1, substr_count($html, '15.09.2026 12:00'));

        $page->sortTable('latestDownload.downloaded_at', 'asc');
        $this->assertSame([$older->getKey(), $newer->getKey()], $page->instance()->getTableRecords()->modelKeys());
    }

    public function testAssignmentTabsRejectNonAssignmentQueries(): void
    {
        $tabs = $this->app->make(MyOffers\Tabs\AssignmentTabs::class);

        $this->expectException(\LogicException::class);

        $tabs->make(null)['available']
            ->getQuery()
            ->modifyQueryUsing(fn ($q) => $q);
    }


    public function testAvailableTabRendersExpectedColumns(): void
    {
        $user = User::factory()->create();

        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles(RoleEnum::CHANNEL_OPERATOR->value);

        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);

        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);

        Assignment::factory()
            ->withBatch()
            ->create([
                'channel_id' => $channel->getKey(),
            ]);

        Filament::setTenant($team, true);
        Filament::auth()->login($user);

        Livewire::test(MyOffers::class)
            ->assertStatus(200)
            ->assertSee(__('my_offers.table.columns.video_title'))
            ->assertSee(__('my_offers.table.columns.uploader'))
            ->assertSee(__('my_offers.table.columns.valid_until'))
            ->assertSee(__('my_offers.table.columns.status'))
            ->assertDontSee(__('my_offers.table.columns.returned_at'));
    }

    public function testCanAccessReturnsFalseWithoutAuthenticatedUser(): void
    {
        Filament::auth()->logout();

        self::assertFalse(MyOffers::canAccess());
    }

    public function testCanAccessReturnsTrueWhenAbilityAllows(): void
    {
        $ability = new class () {
            public function check(User $user): bool
            {
                return $user instanceof User;
            }
        };

        $this->app->instance(AccessChannelPageAbility::class, $ability);

        $user = User::factory()->create();
        Filament::auth()->login($user);

        self::assertTrue(MyOffers::canAccess());
    }

    public function testMergeComponentsAddsZipAnchorWhenChannelExists(): void
    {
        $channel = Channel::factory()->create();

        $this->app->bind(LinkService::class, static fn () => new class () {
            public function getZipSelectedUrlForChannel(Channel $channel, $expires): string
            {
                return 'zip-url-' . $channel->getKey();
            }
        });

        $page = new MyOffersTestPage($channel);

        $components = $page->callMergeComponentsIfChannelExists(['original']);

        self::assertCount(2, $components);
        self::assertSame('filament.standard.components.zip-form-anchor', $components[0]->getView());
    }

    public function testMergeComponentsKeepsComponentsWhenChannelMissing(): void
    {
        $this->app->bind(LinkService::class, static fn () => new class () {
            public function getZipSelectedUrlForChannel(Channel $channel, $expires): string
            {
                return 'zip-url';
            }
        });

        $page = new MyOffersTestPage(null);

        $components = $page->callMergeComponentsIfChannelExists(['original']);

        self::assertSame(['original'], $components);
    }

    public function testBaseQueryFiltersAssignmentsByChannel(): void
    {
        $channel = Channel::factory()->create();
        $otherChannel = Channel::factory()->create();

        $assignments = Assignment::factory()
            ->count(2)
            ->withBatch()
            ->sequence(
                ['channel_id' => $channel->getKey()],
                ['channel_id' => $otherChannel->getKey()],
            )
            ->create();

        $table = $this->app->make(MyOffers\Table\AssignmentTable::class);

        $channelResults = $table->baseQuery($channel)->pluck('id');
        self::assertTrue($channelResults->contains($assignments[0]->getKey()));
        self::assertFalse($channelResults->contains($assignments[1]->getKey()));
    }

    public function testEmptyStateDescriptionMatchesActiveTab(): void
    {
        $page = new MyOffersTestPage(null);

        $page->activeTab = 'downloaded';
        self::assertSame(__('my_offers.messages.no_videos_downloaded'), $page->callEmptyStateDescription());

        $page->activeTab = 'expired';
        self::assertSame(__('my_offers.messages.no_expired_offers'), $page->callEmptyStateDescription());

        $page->activeTab = 'returned';
        self::assertSame(__('my_offers.messages.no_returned_offers'), $page->callEmptyStateDescription());

        $page->activeTab = 'available';
        self::assertSame(__('my_offers.table.empty_state.description'), $page->callEmptyStateDescription());
    }

    public function testGetWidgetDataReturnsChannelId(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);

        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);

        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);

        Filament::setTenant($team, true);
        Filament::auth()->login($user);

        $page = Livewire::test(MyOffers::class);

        self::assertSame(['channelId' => $channel->getKey()], $page->instance()->getWidgetData());
    }

    public function testADeletedVideoCanStillBeFetchedAgainWhileItsFilesExist(): void
    {
        [$channel] = $this->actingOperator();
        $assignment = $this->downloadedOfferOfDeletedVideo($channel, 'Still Stored.mp4');

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'downloaded')
            ->assertTableActionVisible('download_again', $assignment);
    }

    public function testOnceTheFilesAreGoneItCannotBeFetchedAgain(): void
    {
        [$channel] = $this->actingOperator();
        $assignment = $this->downloadedOfferOfDeletedVideo($channel, 'Files Gone.mp4');
        Video::withTrashed()->whereKey($assignment->video_id)
            ->update(['processing_status' => ProcessingStatusEnum::Deleted->value]);

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'downloaded')
            ->assertSee(__('my_offers.table.no_longer_available'))
            ->assertTableActionHidden('download_again', $assignment);
    }
}

class MyOffersTestPage extends MyOffers
{
    public function __construct(private readonly ?Channel $channel)
    {
    }

    public function callMergeComponentsIfChannelExists(array $components): array
    {
        return $this->mergeComponentsIfChannelExists($components);
    }

    public function callEmptyStateDescription(): string
    {
        $assignmentTable = app(MyOffers\Table\AssignmentTable::class);

        $reflection = new \ReflectionClass($assignmentTable);
        $method = $reflection->getMethod('emptyStateDescription');

        return (string)$method->invoke($assignmentTable, $this);
    }

    protected function getCurrentChannel(): ?Channel
    {
        return $this->channel;
    }
}

final class MyOffersResetPage extends MyOffersTestPage
{
    public int $resetCount = 0;

    public function __construct(?Channel $channel)
    {
        parent::__construct($channel);
    }

    public function resetTable(): void
    {
        $this->resetCount++;
    }
}

final readonly class RecordingAssignmentService extends AssignmentService
{
    public Collection $calls;

    public function __construct()
    {
        parent::__construct(new \App\Repository\AssignmentRepository());
        $this->calls = new Collection();
    }

    public function returnAssignment(Assignment $assignment, ?User $user = null): bool
    {
        $this->calls->push([$assignment, $user]);

        return true;
    }

}
