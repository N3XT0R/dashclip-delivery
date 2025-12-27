<?php

declare(strict_types=1);

namespace App\Application\Offer;

use Filament\Pages\Page;

class DispatchZipDownload
{
    /**
     * Handle the dispatching of a zip download for the given assignment IDs.
     * @param Page $page
     * @param iterable $assignmentIds
     * @return void
     */
    public function handle(Page $page, iterable $assignmentIds): void
    {
        $page->dispatch('zip-download', [
            'assignmentIds' => $assignmentIds,
        ]);
    }
}
