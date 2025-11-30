<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages\CreateTeam;
use App\Filament\Resources\TeamResource\Pages\EditTeam;
use App\Filament\Resources\TeamResource\Pages\ListTeams;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class TeamResourceTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function testRegularUserCannotAccessTeamList(): void
    {
        $user = User::factory()->standard()->create();
        $this->actingAs($user);

        Livewire::test(ListTeams::class)
            ->assertStatus(403);
    }

    public function testListTeamsDisplaysCreatedRecords(): void
    {
        $owner = User::factory()->create(['name' => 'Owner User']);
        $team = Team::factory()
            ->forUser($owner)
            ->create(['name' => 'Filament Team']);

        Livewire::test(ListTeams::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$team])
            ->assertTableColumnStateSet('name', $team->name, record: $team)
            ->assertTableColumnStateSet('owner.name', $owner->name, record: $team)
            ->assertTableColumnExists('created_at', record: $team)
            ->assertTableColumnExists('updated_at', record: $team);
    }

    public function testCreateTeamFormStoresTeam(): void
    {
        $owner = User::factory()->create();
        $slug = 'team-'.Str::random(6);

        Livewire::test(CreateTeam::class)
            ->assertStatus(200)
            ->fillForm([
                'slug' => $slug,
                'name' => 'Marketing',
                'owner_id' => $owner->getKey(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Team::class, [
            'slug' => $slug,
            'name' => 'Marketing',
            'owner_id' => $owner->getKey(),
        ]);
    }

    public function testEditTeamFormUpdatesTeam(): void
    {
        $owner = User::factory()->create(['name' => 'Original Owner']);
        $newOwner = User::factory()->create(['name' => 'New Owner']);
        $team = Team::factory()
            ->forUser($owner)
            ->create([
                'name' => 'Old Name',
                'slug' => 'old-slug',
            ]);

        Livewire::test(EditTeam::class, ['record' => $team->getKey()])
            ->assertStatus(200)
            ->assertFormSet([
                'name' => 'Old Name',
                'slug' => 'old-slug',
                'owner_id' => $owner->getKey(),
            ])
            ->fillForm([
                'name' => 'Updated Name',
                'slug' => 'updated-slug',
                'owner_id' => $newOwner->getKey(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $team->refresh();

        $this->assertSame('Updated Name', $team->name);
        $this->assertSame('updated-slug', $team->slug);
        $this->assertSame($newOwner->getKey(), $team->owner_id);
    }
}
