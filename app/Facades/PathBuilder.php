<?php

declare(strict_types=1);

namespace App\Facades;

use App\Models\Clip;
use App\Services\PathBuilderService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string forDropbox(string $basePath, string $relativePath)
 * @method static string forVideo(string $hash, string $ext)
 * @method static string forPreviewByClip(Clip $clip)
 * @method static string join(string ...$parts)
 */
class PathBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PathBuilderService::class;
    }
}
