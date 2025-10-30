<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\Version\LocalVersionService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string getCurrentVersion(): ?string
 */
class Version extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LocalVersionService::class;
    }
}