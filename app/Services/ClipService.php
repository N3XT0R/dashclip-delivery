<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\ClipRepository;

class ClipService
{
    public function __construct(private ClipRepository $clipRepository)
    {
    }


}