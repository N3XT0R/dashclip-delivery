<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Services\ChannelService;
use Throwable;

/**
 * @deprecated use TokenApprovalController instead
 */
class ChannelApprovalController extends Controller
{
    public function __construct(private readonly ChannelService $channelService)
    {
    }

    public function approve(Channel $channel, string $token)
    {
        try {
            $this->channelService->approve($channel, $token);
        } catch (Throwable $e) {
            abort(403, $e->getMessage());
        }
        return view('channels.approved', compact('channel'));
    }
}
