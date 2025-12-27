<?php

declare(strict_types=1);

namespace App\Application\Offer;

use App\Models\Assignment;
use App\Services\AssignmentService;
use Illuminate\Support\Collection;

readonly class ReturnAssignment
{
    public function __construct(private AssignmentService $assignmentService)
    {
    }

    /**
     * Handle the return of assignments for the given offers.
     * @param Collection<Assignment> $offers
     * @return void
     */
    public function handle(Collection $offers): void
    {
        foreach ($offers as $offer) {
            if ($offer instanceof Assignment === false) {
                continue;
            }
            $this->assignmentService->returnAssignment($offer);
        }
    }
}
