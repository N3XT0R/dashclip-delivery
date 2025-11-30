<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Model;

class ChannelTeamPivot extends Model
{
    protected $table = 'team_channel';

    public $timestamps = false;

    protected $fillable = [
        'team_id',
        'channel_id',
        'quota',
    ];
}
