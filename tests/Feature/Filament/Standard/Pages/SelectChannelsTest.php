<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Filament\Standard\Pages\SelectChannels;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

class SelectChannelsTest extends DatabaseTestCase
{
    public function testRegularUserHasAccess(): void
    {
        $regularUser = User::factory()
            ->withOwnTeam()
            ->standard(GuardEnum::DEFAULT->value)->create();
        $this->actingAs($regularUser);

        Livewire::test(SelectChannels::class)
            ->assertStatus(200);
    }
}