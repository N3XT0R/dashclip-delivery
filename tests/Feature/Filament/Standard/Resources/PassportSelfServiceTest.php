<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources;

use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use N3XT0R\FilamentPassportUi\Database\Factories\ClientFactory;
use N3XT0R\FilamentPassportUi\Resources\ClientResource\Pages\ListClients;
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

        Filament::setCurrentPanel(Filament::getPanel('standard'));
        Filament::setTenant($tenant, true);
        Filament::auth()->login($user);
        $this->actingAs($user, 'standard');

        Livewire::test(ListClients::class)
            ->assertCanSeeTableRecords([$ownClient])
            ->assertCanNotSeeTableRecords(
                \N3XT0R\LaravelPassportAuthorizationCore\Models\Passport\Client::query()
                    ->whereKeyNot($ownClient->getKey())
                    ->get()
            );
    }
}
