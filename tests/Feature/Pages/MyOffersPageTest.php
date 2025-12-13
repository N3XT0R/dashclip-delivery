<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Tests\DatabaseTestCase;

class MyOffersPageTest extends DatabaseTestCase
{
    private User $user;

    private Team $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->withOwnTeam()
            ->create();

        $this->tenant = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->tenant, true);
        Filament::auth()->login($this->user);
    }

    public function test_channel_operator_can_access_page(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => RoleEnum::CHANNEL_OPERATOR->value,
            'guard_name' => GuardEnum::STANDARD->value,
        ]);

        $this->user->assignRole($role);

        $response = $this->actingAs($this->user, GuardEnum::STANDARD->value)
            ->get(route('filament.standard.pages.my-offers', ['tenant' => $this->tenant]));

        $response->assertOk();
    }

    public function test_non_operator_cannot_access(): void
    {
        $response = $this->actingAs($this->user, GuardEnum::STANDARD->value)
            ->get(route('filament.standard.pages.my-offers', ['tenant' => $this->tenant]));

        $response->assertForbidden();
    }
}
