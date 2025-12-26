<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ActionToken;
use App\Models\ChannelApplication;

class ChannelAccessApprovalRequestedMail extends AbstractLoggedMail
{

    public function __construct(
        public ChannelApplication $channelApplication,
        public ActionToken $actionToken,
    ) {
    }


    protected function viewName(): string
    {
        return 'emails.channel.access_approval_requested';
    }

}
