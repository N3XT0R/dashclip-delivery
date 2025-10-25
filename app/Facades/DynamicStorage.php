<?php

declare(strict_types=1);

namespace App\Facades;

use App\DTO\FileInfoDto;
use App\Services\DynamicStorageService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Filesystem fromPath(string $path)
 * @method static Collection<FileInfoDto> listFiles(Filesystem $disk, string $basePath = '')
 * @method static string getHashForFileInfoDto(Filesystem $disk, FileInfoDto $file)
 * @method static string getHashForFilePath(Filesystem $disk, string $relativePath)
 */
class DynamicStorage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DynamicStorageService::class;
    }
}