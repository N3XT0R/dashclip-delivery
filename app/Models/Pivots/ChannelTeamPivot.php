<?php

namespace App\Models\Pivots;

use App\Models\Channel;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelTeamPivot extends Model
{
    protected $table = 'channel_team';

    public $timestamps = false;

    protected $fillable = [
        'team_id',
        'channel_id',
        'quota',
    ];

    protected $casts = [
        'quota' => 'integer',
        'team_id' => 'integer',
        'channel_id' => 'integer',
    ];


    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
