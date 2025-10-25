<?php

declare(strict_types=1);

namespace App\Support;

final class PathBuilder
{
    public static function forVideo(string $hash, string $ext): string
    {
        $sub = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        return sprintf('videos/%s/%s%s', $sub, $hash, $ext !== '' ? ".{$ext}" : '');
    }

    public static function forPreview(string $hash): string
    {
        $sub = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        return sprintf('previews/%s/%s.jpg', $sub, $hash);
    }
}