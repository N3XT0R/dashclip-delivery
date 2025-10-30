<?php

declare(strict_types=1);

namespace App\Models;

use App\Facades\PathBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Clip extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'start_sec',
        'end_sec',
        'note',
        'bundle_key',
        'role',
        'submitted_by'
    ];

    protected $appends = [
        'start_time',
        'end_time',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function getPreviewPath(): string
    {
        $videoId = $this->getAttribute('video')->getKey();
        $hash = md5($videoId.'_'.$this->getAttribute('start_sec').'_'.$this->getAttribute('end_sec'));
        return "previews/{$hash}.mp4";
    }

    public function getNewPreviewPath(): string
    {
        return PathBuilder::forPreviewByHash($this->video->hash);
    }

    protected function startTime(): Attribute
    {
        return Attribute::get(
            fn() => $this->start_sec !== null
                ? gmdate('i:s', (int)$this->start_sec)
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
}

