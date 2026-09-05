<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Clip extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'video_id',
        'preview_disk',
        'preview_path',
        'start_sec',
        'end_sec',
        'note',
        'bundle_key',
        'role',
        'submitted_by',
        'user_id',
        'preferred_channel',
        'preferred_channel_id',
    ];

    protected $appends = [
        'start_time',
        'end_time',
        'duration',
        'human_readable_duration',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The channel the submitter asked this clip's video to be assigned to.
     * Null when no preference was given or the raw value could not be resolved.
     */
    public function preferredChannel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'preferred_channel_id');
    }

    public function setUser(User $user): self
    {
        $this->setAttribute('user_id', $user->getKey());
        $this->setAttribute('submitted_by', $user->display_name);
        return $this;
    }

    protected function startTime(): Attribute
    {
        return Attribute::get(
            fn() => $this->start_sec !== null
                ? gmdate('i:s', (int)$this->start_sec)
                : null,
        );
    }

    protected function duration(): Attribute
    {
        return Attribute::get(
            fn() => ($this->start_sec !== null && $this->end_sec !== null)
                ? $this->end_sec - $this->start_sec
                : null,
        );
    }

    protected function humanReadableDuration(): Attribute
    {
        return Attribute::get(
            fn() => ($this->start_sec !== null && $this->end_sec !== null)
                ? gmdate('i:s', (int)($this->end_sec - $this->start_sec))
                : null,
        );
    }

    protected function endTime(): Attribute
    {
        return Attribute::get(
            fn() => $this->end_sec !== null
                ? gmdate('i:s', (int)$this->end_sec)
                : null,
        );
    }

    public function getDisk(): Filesystem
    {
        return Storage::disk($this->getAttribute('preview_disk'));
    }
}

