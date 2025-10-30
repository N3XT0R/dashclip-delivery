<?php

declare(strict_types=1);

namespace App\Services;

class OfferService
{
    public function __construct(private AssignmentService $assignments)
    {
    }
    
}