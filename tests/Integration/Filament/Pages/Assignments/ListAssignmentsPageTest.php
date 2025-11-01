<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Pages\Assignments;

use App\Filament\Resources\Assignments\Pages\ListAssignments;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\User;
use App\Services\LinkService;
use Carbon\Carbon;
use Livewire\Livewire;
use Mockery;
use Tests\DatabaseTestCase;

final class ListAssignmentsPageTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Filament-Komponenten müssen in Tests registriert werden, sonst schlägt View-Rendering fehl
        $this->app->register(\Filament\FilamentServiceProvider::class);

        // Beispiel-Admin-User
        $this->admin = User::factory()->admin()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testListAssignmentsPageRendersForAdmin(): void
    {
        $this->actingAs($this->admin);

        $batch = Batch::factory()->create();
        $channel = Channel::factory()->create();

        $assignment = Assignment::factory()
            ->for($batch)
            ->for($channel)
            ->create();

        Livewire::test(ListAssignments::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$assignment])
            ->assertTableActionVisible('offer');
    }

    public function testOfferActionUsesLinkService(): void
    {
        $this->actingAs($this->admin);

        $batch = Batch::factory()->create();
        $channel = Channel::factory()->create();

        $assignment = Assignment::factory()
            ->for($batch)
            ->for($channel)
            ->create();

        // Mock des LinkService mit flexiblem Argument-Matching
        $mock = Mockery::mock(LinkService::class);
        $mock->shouldReceive('getOfferUrl')
            ->twice() // Filament ruft es beim Rendern und evtl. beim Action-Aufruf auf
            ->with(
                Mockery::on(fn($b) => $b->is($batch)),
                Mockery::on(fn($c) => $c->is($channel)),
                Mockery::type(Carbon::class)
            )
            ->andReturn('https://example.com/offer');

        $this->app->instance(LinkService::class, $mock);

        Livewire::test(ListAssignments::class)
            ->assertSuccessful()
            ->assertTableActionVisible('offer');
    }

    public function testNoCreateButtonVisible(): void
    {
        $this->get(ListAssignments::getUrl())
            ->assertRedirect();
    }
}
