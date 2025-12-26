<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Application\Channel\Application\ApproveChannelAccess;
use App\Enum\TokenPurposeEnum;
use App\Events\ActionToken\ActionTokenConsumed;
use App\Models\ChannelApplication;
use Illuminate\Contracts\Queue\ShouldQueue;

final readonly class HandleChannelAccessApproval implements ShouldQueue
{
    public function __construct(
        private ApproveChannelAccess $approveChannelAccess
    ) {
    }

    public function handle(ActionTokenConsumed $event): void
    {
        $token = $event->token;

        if ($token->purpose !== TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL->value) {
            return;
        }

        if (!$token->subject instanceof ChannelApplication) {
            return;
        }

        $this->approveChannelAccess->handle(
            $token->subject
        );
    }
}
