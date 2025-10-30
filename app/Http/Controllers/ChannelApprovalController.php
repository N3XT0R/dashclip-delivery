<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Channel;

class ChannelApprovalController extends Controller
{
    public function approve(Channel $channel, string $token)
    {
        $expected = sha1($channel->email.config('app.key'));

        if ($token !== $expected) {
            abort(403, 'Ungültiger Bestätigungslink.');
        }

        $channel->update([
            'is_video_reception_paused' => false,
            'approved_at' => now(),
        ]);

        return view('channels.approved', compact('channel'));
    }
}