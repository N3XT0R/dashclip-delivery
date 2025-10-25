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

    public static function forPreview(int $id, int $start, int $end): string
    {
        $hash = md5($id.'_'.$start.'_'.$end);
        return self::forPreviewByHash($hash);
    }

    public static function forPreviewByHash(string $hash): string
    {
        return sprintf('previews/%s.mp4', $hash);
    }

    public static function forDropbox(string $root, string $dstRel): string
    {
        return self::join($root, $dstRel);
    }

    public static function join(string ...$parts): string
    {
        return '/'.trim(implode('/', array_filter($parts)), '/');
    }
}