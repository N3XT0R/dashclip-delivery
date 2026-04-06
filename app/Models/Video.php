<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Video extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'hash',
        'ext',
        'bytes',
        'path',
        'meta',
        'original_name',
        'disk',
        'team_id',
        'processing_status',
    ];
    protected $casts = [
        'meta' => 'array',
        'processing_status' => ProcessingStatusEnum::class,
    ];

    protected array $append = [
        'human_readable_size',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'id',
                'hash',
                'original_name',
                'disk',
                'path',
            ]);
    }


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

    public function assignmentWithNote(): HasOne
    {
        return $this->hasOne(Assignment::class)
            ->whereNotNull('note')
            ->orderBy('updated_at', 'desc');
    }

    public function assignmentsPickedUp(): HasMany
    {
        return $this->assignments()
            ->where('status', StatusEnum::PICKEDUP->value);
    }
}
