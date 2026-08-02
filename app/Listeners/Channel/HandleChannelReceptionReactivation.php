<?php

declare(strict_types=1);

namespace App\Listeners\Channel;

use App\Enum\TokenPurposeEnum;
use App\Events\ActionToken\ActionTokenConsumed;
use App\Models\Channel;

final readonly class HandleChannelReceptionReactivation
{
    public function handle(ActionTokenConsumed $event): void
    {
        $token = $event->token;

        if ($token->purpose !== TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value) {
            return;
        }

        if (!$token->subject instanceof Channel) {
            return;
        }

        $token->subject->update(['is_video_reception_paused' => false]);
    }
}
