<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Assignment;

class AssignmentRepository
{
    public function create(array $data): Assignment
    {
        return Assignment::query()->create($data);
    }
}