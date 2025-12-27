<?php

declare(strict_types=1);

namespace App\Application\Offer;

use Filament\Pages\Page;

class DispatchZipDownload
{
    public function handle(Page $page, iterable $assignmentIds): void
    {
        $page->dispatch('zip-download', [
            'assignmentIds' => $assignmentIds,
        ]);
    }
}
