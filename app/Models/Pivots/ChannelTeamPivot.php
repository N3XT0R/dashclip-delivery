<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Model;

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
}
