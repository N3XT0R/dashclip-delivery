<?php

declare(strict_types=1);

namespace Tests\Feature\Standard\Pages;

use App\Auth\Abilities\AccessChannelPageAbility;
use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Pages\MyOffers;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\User;
use App\Repository\TeamRepository;
use App\Services\AssignmentService;
use App\Services\LinkService;
use Filament\Facades\Filament;
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

    public function testTabsAreRendered(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);

        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);

        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user);

        Filament::setTenant($team, true);
        Filament::auth()->login($user);

        Livewire::test(MyOffers::class)
            ->assertSee(__('my_offers.tabs.available'))
            ->assertSee(__('my_offers.tabs.downloaded'))
            ->assertSee(__('my_offers.tabs.expired'))
            ->assertSee(__('my_offers.tabs.returned'));
    }

    public function testZipFormAnchorIsRenderedWhenChannelExists(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);

        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);

        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user);

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
        $channel->channelUsers()->attach($user);

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

    public function testAssignmentTabsRejectNonAssignmentQueries(): void
    {
        $tabs = $this->app->make(MyOffers\Tabs\AssignmentTabs::class);

        $this->expectException(\LogicException::class);

        $tabs->make(null)['available']
            ->getQuery()
            ->modifyQueryUsing(fn($q) => $q);
    }


    public function testAvailableTabRendersExpectedColumns(): void
    {
        $user = User::factory()->create();

        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles(RoleEnum::CHANNEL_OPERATOR->value);

        $team = $this->app->make(TeamRepository::class)->createOwnTeamForUser($user);

        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user);

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
        $ability = new class {
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

        $this->app->bind(LinkService::class, static fn() => new class {
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
        $this->app->bind(LinkService::class, static fn() => new class {
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
        $channel->channelUsers()->attach($user);

        Filament::setTenant($team, true);
        Filament::auth()->login($user);

        $page = Livewire::test(MyOffers::class);

        self::assertSame(['channelId' => $channel->getKey()], $page->instance()->getWidgetData());
    }

    public function testReturnAssignmentsDelegatesToServiceAndResetsTable(): void
    {
        $user = User::factory()->create();
        $assignments = Assignment::factory()->count(2)->withBatch()->create();

        $assignmentService = new RecordingAssignmentService();
        $this->app->instance(AssignmentService::class, $assignmentService);

        $page = new MyOffersResetPage(null);

        $this->actingAs($user, GuardEnum::STANDARD->value);

        $page->returnAssignments(new Collection($assignments));

        self::assertSame(2, $assignmentService->calls->count());
        self::assertSame(1, $page->resetCount);
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
        $method->setAccessible(true);

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
