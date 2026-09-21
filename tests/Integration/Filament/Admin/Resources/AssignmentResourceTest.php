<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Assignments\AssignmentResource;
use App\Filament\Admin\Resources\Assignments\Pages\ListAssignments;
use App\Filament\Admin\Resources\Videos\VideoResource;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\User;
use App\Models\Video;
use Carbon\Carbon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

class AssignmentResourceTest extends DatabaseTestCase
{
    public function testOfferActionGeneratesSignedUrl(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        $batch = Batch::factory()->type('assign')->create();
        $channel = Channel::factory()->create();
        $assignment = Assignment::factory()
            ->for($channel)
            ->withBatch($batch)
            ->create();

        $page = app(ListAssignments::class);
        $table = AssignmentResource::table(Table::make($page));

        $action = $table->getFlatActions()['offer'];
        $action->record($assignment);

        $url = $action->getUrl();
        $expected = URL::temporarySignedRoute(
            'offer.show',
            Carbon::now()->addDay(),
            ['batch' => $batch->id, 'channel' => $channel->id]
        );

        $this->assertSame($expected, $url);

        Carbon::setTestNow();
    }

    public function testListShowsOffersOfDeletedVideos(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $video = Video::factory()->create(['original_name' => 'Removed Admin Offer.mp4']);
        $assignment = Assignment::factory()->forVideo($video)->withBatch(Batch::factory()->type('assign')->create())->create();
        $video->delete();

        Livewire::test(ListAssignments::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$assignment])
            ->assertSee('Removed Admin Offer.mp4')
            ->assertSee(__('filament.admin.labels.deleted'));
    }

    public function testVideoColumnLinksNonDeletedVideoAndHidesLinkForDeletedVideo(): void
    {
        $video = Video::factory()->create();
        $assignment = Assignment::factory()->forVideo($video)->withBatch(Batch::factory()->type('assign')->create())
            ->create();

        $deletedVideo = Video::factory()->create();
        $deletedAssignment = Assignment::factory()->forVideo($deletedVideo)
            ->withBatch(Batch::factory()->type('assign')->create())
            ->create();
        $deletedVideo->delete();

        $page = app(ListAssignments::class);
        $table = AssignmentResource::table(Table::make($page));
        $column = $table->getColumn('videoWithTrashed.original_name');

        $column->record($assignment->fresh());
        $this->assertSame(VideoResource::getUrl('view', ['record' => $video]), $column->getUrl());

        $column->record($deletedAssignment->fresh());
        $this->assertNull($column->getUrl());
    }
}
