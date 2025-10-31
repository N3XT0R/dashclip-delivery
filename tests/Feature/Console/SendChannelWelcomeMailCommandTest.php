<?php

declare(strict_types=1);

namespace Console;

namespace Tests\Feature\Console;

use App\Mail\ChannelWelcomeMail;
use App\Models\Channel;
use Illuminate\Support\Facades\Mail;
use Tests\DatabaseTestCase;

class SendChannelWelcomeMailCommandTest extends DatabaseTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        Channel::query()->delete();
    }


    public function testShowsWarningIfNoChannelsFound(): void
    {
        $this->artisan('channels:send-welcome')
            ->expectsOutput('Keine passenden Kanäle gefunden.')
            ->assertExitCode(0);
    }

    public function testPerformsDryRunAndShowsTable(): void
    {
        Channel::factory()->create([
            'name' => 'Test Channel',
            'email' => 'test@example.com',
            'approved_at' => null,
        ]);

        $this->artisan('channels:send-welcome --dry')
            ->expectsOutputToContain('🧪 Dry-Run: Es würden folgende Kanäle angeschrieben werden:')
            ->expectsOutputToContain('Test Channel')
            ->expectsOutputToContain('Gesamt: 1 Kanal(e)')
            ->assertExitCode(0);
    }

    public function testSendsMailsToUnapprovedChannels(): void
    {
        Mail::fake();

        $channel = Channel::factory()->create([
            'email' => 'test@example.com',
            'approved_at' => null,
        ]);

        $this->artisan('channels:send-welcome')
            ->expectsOutputToContain('📬 Sende Willkommens-Mail(s) an 1 Kanal(e)...')
            ->expectsOutputToContain('Versand abgeschlossen.')
            ->assertExitCode(0);

        Mail::assertSent(ChannelWelcomeMail::class, function ($mail) use ($channel) {
            return $mail->hasTo($channel->email);
        });
    }
}