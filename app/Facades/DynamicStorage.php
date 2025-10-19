<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\DynamicStorageService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Filesystem fromPath(string $path)
 */
class DynamicStorage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DynamicStorageService::class;
    }
}