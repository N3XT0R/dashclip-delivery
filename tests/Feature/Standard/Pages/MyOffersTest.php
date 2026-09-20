<?php

declare(strict_types=1);

namespace Tests\Feature\Standard\Pages;

use App\Auth\Abilities\AccessChannelPageAbility;
use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\StatusEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Exports\OfferExporter;
use App\Filament\Standard\Pages\MyOffers;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Download;
use App\Models\User;
use App\Repository\TeamRepository;
use App\Services\AssignmentService;
use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Bus;
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

    public function testOffersWithDeletedVideosRenderWithoutAPreview(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);
        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);
        $assignment = Assignment::factory()->forChannel($channel)->create();
        $assignment->video->delete();

        Filament::setTenant($team, true);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        $this->get(MyOffers::getUrl())
            ->assertOk()
            ->assertSee('images/status/no_preview.jpg');
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

    private function operatorWithOffer(string $status = 'notified'): array
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);
        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user, ['is_user_verified' => true]);
        $assignment = Assignment::factory()->withBatch()->create([
            'channel_id' => $channel->getKey(), 'status' => $status, 'expires_at' => now()->addDays(3),
        ]);
        Filament::setTenant($team, true);
        Filament::auth()->login($user);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        return [$user, $channel, $assignment];
    }

    public function testBulkDownloadStartsAnOfferExportForTheSelection(): void
    {
        Bus::fake();
        [$user, $channel, $assignment] = $this->operatorWithOffer();

        Livewire::test(MyOffers::class, ['activeTab' => 'available'])
            ->loadTable()
            ->assertTableBulkActionVisible('download_selected')
            ->callTableBulkAction('download_selected', [$assignment])
            ->assertHasNoTableBulkActionErrors();

        $export = Export::query()->sole();
        $this->assertSame(OfferExporter::class, $export->exporter);
        $this->assertTrue($export->user->is($user));
        $this->assertSame(1, $export->total_rows);
    }

    public function testExportDialogUsesOfferWordingInsteadOfModelNames(): void
    {
        Bus::fake();
        [, , $assignment] = $this->operatorWithOffer();

        $page = Livewire::test(MyOffers::class, ['activeTab' => 'available'])->loadTable();
        $table = $page->instance()->getTable();

        $this->assertSame(__('my_offers.offer'), $table->getModelLabel());
        $this->assertSame(__('my_offers.offers'), $table->getPluralModelLabel());
        $this->assertStringContainsString(__('my_offers.offers'), (string) $table->getBulkAction('download_selected')->getModalHeading());
        $this->assertStringNotContainsString('Assignment', (string) $table->getBulkAction('download_selected')->getModalHeading());
    }

    public function testRowDownloadExportsExactlyItsOffer(): void
    {
        Bus::fake();
        [, , $assignment] = $this->operatorWithOffer();
        Assignment::factory()->withBatch()->create(['channel_id' => $assignment->channel_id, 'status' => 'notified']);

        Livewire::test(MyOffers::class, ['activeTab' => 'available'])
            ->loadTable()
            ->callTableAction('download', $assignment);

        $this->assertSame(1, Export::query()->sole()->total_rows);
    }

    public function testDownloadAgainIsAvailableForDownloadedOffers(): void
    {
        Bus::fake();
        [, , $assignment] = $this->operatorWithOffer('picked_up');
        Download::factory()->create(['assignment_id' => $assignment->getKey(), 'downloaded_at' => now()]);

        Livewire::test(MyOffers::class, ['activeTab' => 'downloaded'])
            ->loadTable()
            ->assertTableActionVisible('download_again', $assignment)
            ->callTableAction('download_again', $assignment);

        $this->assertSame(1, Export::query()->sole()->total_rows);
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
