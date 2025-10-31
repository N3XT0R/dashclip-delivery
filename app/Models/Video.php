<?php

declare(strict_types=1);

namespace App\Models;

use App\Facades\PathBuilder;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'hash',
        'ext',
        'bytes',
        'path',
        'meta',
        'original_name',
        'disk',
        'preview_url'
    ];
    protected $casts = ['meta' => 'array'];

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function clips(): HasMany
    {
        return $this->hasMany(Clip::class);
    }

    public function getDisk(): Filesystem
    {
        return Storage::disk($this->getAttribute('disk'));
    }


    public function getPreviewPath(): ?string
    {
        $hash = $this->getAttribute('hash');
        if (empty($hash)) {
            return null;
        }

        $path = PathBuilder::forPreviewByHash($hash);

        $disk = $this->getDisk();
        if (!$disk->exists($path)) {
            $clip = $this->clips()->first();
            $path = $clip?->getPreviewPath();
            if (empty($path) || !$disk->exists($path)) {
                return null;
            }
        }

        return $path;
    }

    protected static function booted(): void
    {
        static::deleting(static function (Video $video) {
            $path = $video->getAttribute('path');
            if (!$path) {
                return true;
            }

            try {
                $storageDisk = $video->getDisk();
                $targetDisk = config('preview.default_disk', 'public');
                $previewDisk = Storage::disk($targetDisk);
                $previewPath = $video->getPreviewPath();

                if ($storageDisk->exists($path) && !$storageDisk->delete($path)) {
                    \Log::warning('video delete failed', ['video_id' => $video->getKey(), 'path' => $path]);
                    return false;
                }

                if (null !== $previewPath && $previewDisk->exists($previewPath) && !$previewDisk->delete($previewPath)) {
                    \Log::warning('preview delete failed', ['video_id' => $video->getKey(), 'path' => $previewPath]);
                    return false;
                }
            } catch (\Throwable $e) {
                \Log::error('File delete threw',
                    ['video_id' => $video->getKey(), 'path' => $path, 'err' => $e->getMessage(), 'exception' => $e]);
                return false;
            }

            return true;
        });
    }
}