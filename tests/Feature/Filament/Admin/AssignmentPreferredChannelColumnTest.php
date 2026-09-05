<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin;

use App\Filament\Admin\Resources\Assignments\Pages\ListAssignments;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\User;
use App\Models\Video;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

class AssignmentPreferredChannelColumnTest extends DatabaseTestCase
{
    public function testListShowsPreferredChannelColumnAndFilters(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $channel = Channel::factory()->create();
        $batch = Batch::factory()->create();

        $viaPreferred = Assignment::query()->create([
            'video_id' => Video::factory()->create()->getKey(),
            'channel_id' => $channel->getKey(),
            'batch_id' => $batch->getKey(),
            'status' => 'queued',
            'via_preferred_channel' => true,
        ]);
        $viaAlgorithm = Assignment::query()->create([
            'video_id' => Video::factory()->create()->getKey(),
            'channel_id' => $channel->getKey(),
            'batch_id' => $batch->getKey(),
            'status' => 'queued',
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
