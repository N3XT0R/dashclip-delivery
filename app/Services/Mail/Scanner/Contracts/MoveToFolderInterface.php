<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner\Contracts;

interface MoveToFolderInterface
{
    public function getMoveToFolderPath(): string;
}