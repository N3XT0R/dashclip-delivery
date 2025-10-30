<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\Channel;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

class OfferService
{
    public function __construct(private AssignmentService $assignments)
    {
    }

    /**
     * Prepares all data for OfferPage
     * @param  Batch  $batch
     * @param  Channel  $channel
     * @return array<string, mixed>
     */
    public function prepareOfferViewData(Batch $batch, Channel $channel): array
    {
        $items = $this->assignments
            ->fetchPending($batch, $channel)
            ->loadMissing('video.clips');

        $pickedUp = $this->assignments
            ->fetchPickedUp($batch, $channel)
            ->loadMissing('video.clips');

        $this->addTempUrlToAssignments($items);
        $this->addTempUrlToAssignments($pickedUp);

        $linkService = app(LinkService::class);
        $zipPostUrl = $linkService->getZipSelectedUrl($batch, $channel, now()->addHours(6));

        return compact('batch', 'channel', 'items', 'zipPostUrl', 'pickedUp');
    }


    protected function addTempUrlToAssignments(Collection $items): void
    {
        $isAuthenticated = Filament::auth()?->check();

        foreach ($items as $assignment) {
            $assignment->temp_url = $this->assignments->prepareDownload(
                assignment: $assignment,
                skipTracking: $isAuthenticated === true
            );
        }
    }
}