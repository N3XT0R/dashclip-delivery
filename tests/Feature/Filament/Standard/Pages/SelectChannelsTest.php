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
        $guard = GuardEnum::STANDARD;
        $regularUser = User::factory()
            ->withOwnTeam()
            ->standard($guard)
            ->create();

        $this->actingAs($regularUser, $guard->value);

        Livewire::test(SelectChannels::class)
            ->assertStatus(200);
    }
}