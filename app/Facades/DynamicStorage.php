<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\DynamicStorageService;
use Illuminate\Support\Facades\Facade;

class DynamicStorage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DynamicStorageService::class;
    }
}