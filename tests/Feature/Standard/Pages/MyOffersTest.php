<?php

declare(strict_types=1);

namespace Tests\Feature\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Pages\MyOffers;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
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
        $channel->channelUsers()->attach($user);

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

}
