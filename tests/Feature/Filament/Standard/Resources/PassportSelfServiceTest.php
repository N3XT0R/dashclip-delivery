<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use N3XT0R\FilamentPassportUi\Database\Factories\ClientFactory;
use N3XT0R\FilamentPassportUi\Resources\ClientResource\Pages\ListClients;
use N3XT0R\FilamentPassportUi\Resources\TokenResource\Pages\ListTokens;
use N3XT0R\LaravelPassportAuthorizationCore\Database\Factories\TokenFactory;
use Tests\DatabaseTestCase;

final class PassportSelfServiceTest extends DatabaseTestCase
{
    public function testStandardUserOnlySeesOwnClients(): void
    {
        $user = User::factory()->withOwnTeam()->standard()->create();
        $otherUser = User::factory()->withOwnTeam()->standard()->create();
        $tenant = app(TeamRepository::class)->getDefaultTeamForUser($user);

        $ownClient = ClientFactory::new()->create([
            'owner_id' => $user->getKey(),
            'owner_type' => $user->getMorphClass(),
        ]);
        ClientFactory::new()->create([
            'owner_id' => $otherUser->getKey(),
            'owner_type' => $otherUser->getMorphClass(),
        ]);

        Filament::setCurrentPanel(Filament::getPanel(PanelEnum::STANDARD->value));
        Filament::setTenant($tenant, true);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        Livewire::test(ListClients::class)
            ->assertCanSeeTableRecords([$ownClient])
            ->assertCanNotSeeTableRecords(
                \N3XT0R\LaravelPassportAuthorizationCore\Models\Passport\Client::query()
                    ->whereKeyNot($ownClient->getKey())
                    ->get()
            );
    }

    public function testStandardUserOnlySeesOwnTokens(): void
    {
        $user = User::factory()->withOwnTeam()->standard()->create();
        $otherUser = User::factory()->withOwnTeam()->standard()->create();
        $tenant = app(TeamRepository::class)->getDefaultTeamForUser($user);

        $ownToken = TokenFactory::new()->withUserId($user->getKey())->create();
        TokenFactory::new()->withUserId($otherUser->getKey())->create();

        Filament::setCurrentPanel(Filament::getPanel(PanelEnum::STANDARD->value));
        Filament::setTenant($tenant, true);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        Livewire::test(ListTokens::class)
            ->assertCanSeeTableRecords([$ownToken])
            ->assertCanNotSeeTableRecords(
                \Laravel\Passport\Token::query()
                    ->whereKeyNot($ownToken->getKey())
                    ->get()
            );
    }
}
