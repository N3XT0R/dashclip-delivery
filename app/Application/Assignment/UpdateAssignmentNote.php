<?php

declare(strict_types=1);

namespace App\Application\Assignment;

use App\Models\Assignment;
use App\Models\User;
use App\Services\AssignmentService;

readonly class UpdateAssignmentNote
{
    public function __construct(private AssignmentService $assignmentService)
    {
    }

    public function handle(Assignment $assignment, string $note, ?User $user = null): void
    {
        $this->assignmentService->updateNote($assignment, $note, $user);
    }
}
