<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\PathBuilderService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string forDropbox(string $basePath, string $relativePath)
 * @method static string forVideo(string $hash, string $ext)
 * @method static string forPreview(int $id, int $start, int $end)
 * @method static string forPreviewByHash(string $fileHash)
 * @method static string join(string ...$parts)
 */
class PathBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PathBuilderService::class;
    }
}