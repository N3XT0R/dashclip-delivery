<?php

declare(strict_types=1);

namespace App\Support;

final class PathBuilder
{
    /**
     * Get the video path by its hash and extension.
     * @param  string  $hash
     * @param  string  $ext
     * @return string
     */
    public static function forVideo(string $hash, string $ext): string
    {
        $sub = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        return sprintf('videos/%s/%s%s', $sub, $hash, $ext !== '' ? ".{$ext}" : '');
    }

    /**
     * Get the preview path by video ID and time range.
     * @param  int  $id
     * @param  int  $start
     * @param  int  $end
     * @return string
     */
    public static function forPreview(int $id, int $start, int $end): string
    {
        $hash = md5($id.'_'.$start.'_'.$end);
        return sprintf('previews/%s.mp4', $hash);
    }

    /**
     * Get the preview path by video file hash.
     * @param  string  $fileHash
     * @return string
     * @note This is used to get the preview path based on the video file hash, it will replace forPreview in the future.
     */
    public static function forPreviewByHash(string $fileHash): string
    {
        $sub = substr($fileHash, 0, 2).'/'.substr($fileHash, 2, 2);
        return sprintf('previews/%s/%s.mp4', $sub, $fileHash);
    }

    /**
     * Build a path for Dropbox storage.
     * @param  string  $root
     * @param  string  $dstRel
     * @return string
     */
    public static function forDropbox(string $root, string $dstRel): string
    {
        return self::join($root, $dstRel);
    }

    /**
     * Join multiple path parts into a single path.
     * @param  string  ...$parts
     * @return string
     */
    public static function join(string ...$parts): string
    {
        return '/'.trim(implode('/', array_filter($parts)), '/');
    }
}