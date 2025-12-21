<?php

declare(strict_types=1);

namespace App\Models;

use App\Facades\PathBuilder;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

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
        'preview_url',
        'team_id'
    ];
    protected $casts = [
        'meta' => 'array'
    ];

    protected $append = [
        'human_readable_size',
    ];


    public function scopeHasUsersClips(Builder $query, User $user): Builder
    {
        return $query->whereHas('clips', function ($q) use ($user) {
            $q->where('user_id', $user->getKey());
        });
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function clips(): HasMany
    {
        return $this->hasMany(Clip::class);
    }


    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
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

    protected function humanReadableSize(): Attribute
    {
        return Attribute::make(get: function () {
            $bytes = $this->getAttribute('bytes');
            if ($bytes === null) {
                return null;
            }

            return Number::fileSize($bytes);
        });
    }
}