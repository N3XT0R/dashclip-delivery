<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin;

use App\Enum\PanelEnum;
use App\Filament\Admin\Resources\Assignments\Pages\ListAssignments;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

class AssignmentPreferredChannelColumnTest extends DatabaseTestCase
{
    public function testListShowsPreferredChannelColumnAndFilters(): void
    {
        Filament::setCurrentPanel(PanelEnum::ADMIN->value);
        $this->actingAs(User::factory()->admin()->create());

        $channel = Channel::factory()->create();

        $viaPreferred = Assignment::factory()->forChannel($channel)->create([
            'via_preferred_channel' => true,
        ]);
        $viaAlgorithm = Assignment::factory()->forChannel($channel)->create([
            'via_preferred_channel' => false,
        ]);

        Livewire::test(ListAssignments::class)
            ->assertCanSeeTableRecords([$viaPreferred, $viaAlgorithm])
            ->assertTableColumnExists('via_preferred_channel')
            ->filterTable('via_preferred_channel', true)
            ->assertCanSeeTableRecords([$viaPreferred])
            ->assertCanNotSeeTableRecords([$viaAlgorithm]);
    }
}
