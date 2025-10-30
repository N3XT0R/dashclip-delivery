<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Assignment;
use App\Models\OfferLinkClick;
use App\Models\User;

class OfferLinkRepository
{

    public function createOfferLinkClick(Assignment $assignment, string $userAgent, ?User $user = null): OfferLinkClick
    {
        return OfferLinkClick::create([
            'assignment_id' => $assignment->getKey(),
            'clicked_at' => now(),
            'user_id' => $user?->getKey(),
            'user_agent' => substr($userAgent, 0, 500),
        ]);
    }
}