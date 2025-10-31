<?php

declare(strict_types=1);

namespace Console;

namespace Tests\Feature\Console;

use App\Models\Channel;
use Tests\DatabaseTestCase;

class SendChannelWelcomeMailCommandTest extends DatabaseTestCase
{
    public function testShowsWarningIfNoChannelsFound(): void
    {
        Channel::query()->delete();
        $this->artisan('channels:send-welcome')
            ->expectsOutput('Keine passenden Kanäle gefunden.')
            ->assertExitCode(0);
    }
}