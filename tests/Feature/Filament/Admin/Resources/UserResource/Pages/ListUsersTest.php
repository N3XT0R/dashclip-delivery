<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin\Resources\UserResource\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Admin\Resources\UserResource;
use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class ListUsersTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin, 'web');
    }

    public function testListUsersHeaderShowsCreateAction(): void
    {
        Livewire::test(ListUsers::class)
            ->assertStatus(200)
            ->assertActionVisible('create');
    }

    public function testListUsersShowsRecordActions(): void
    {
        $user = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->assertStatus(200)
            ->assertTableActionVisible('edit', $user)
            ->assertTableActionVisible('resetPassword', $user);
    }

    public function testTheViewOfAnotherUserCanBeOpenedFromTheList(): void
    {
        $operator = User::factory()->standard()->create();

        Livewire::test(ListUsers::class)
            ->assertTableActionVisible('impersonate', $operator)
            ->callTableAction('impersonate', $operator)
            ->assertRedirect(Filament::getPanel(PanelEnum::STANDARD->value)->getUrl());

        self::assertTrue(app(ImpersonationService::class)->isActive());
        self::assertTrue(auth(GuardEnum::STANDARD->value)->user()->is($operator));
    }

    public function testTheViewIsNotOfferedForAdministratorsOrForOneself(): void
    {
        $otherAdmin = User::factory()->admin()->create();

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('impersonate', $otherAdmin)
            ->assertTableActionHidden('impersonate', $this->admin);
    }

    public function testTheViewOfASecondUserCanBeOpenedAfterEndingTheFirstOne(): void
    {
        $first = User::factory()->standard()->create();
        $second = User::factory()->standard()->create();

        Livewire::test(ListUsers::class)->callTableAction('impersonate', $first);
        $this->post(route('impersonation.stop'))->assertRedirect();

        Livewire::test(ListUsers::class)
            ->assertTableActionVisible('impersonate', $second)
            ->callTableAction('impersonate', $second)
            ->assertRedirect(Filament::getPanel(PanelEnum::STANDARD->value)->getUrl());

        self::assertTrue(auth(GuardEnum::STANDARD->value)->user()->is($second));
    }

    public function testEveryColumnOfTheUserListCanBeHidden(): void
    {
        $table = UserResource::table(Table::make(app(ListUsers::class)));

        foreach ($table->getColumns() as $name => $column) {
            self::assertTrue($column->isToggleable(), $name . ' cannot be hidden');
        }
    }
}
