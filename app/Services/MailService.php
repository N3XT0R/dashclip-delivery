<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChannelApplication;

class MailService
{
    public function sendChannelAccessApprovalRequestedMail(string $owner, ChannelApplication $channelApplication): void
    {
        // Logic to send the email
    }
}
