<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Channel;

class ChannelWelcomeMail extends AbstractLoggedMail
{

    public function __construct(public Channel $channel)
    {
    }


    protected function viewName(): string
    {
        // TODO: Implement viewName() method.
    }

}