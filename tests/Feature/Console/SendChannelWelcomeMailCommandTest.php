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

        Mail::assertQueued(
            ChannelWelcomeMail::class,
            static function ($mail) use ($channel) {
                return $mail->hasTo($channel->email);
            });
    }

    public function testDoesNotSendMailsToApprovedChannelsWithoutForce(): void
    {
        Mail::fake();

        Channel::factory()->create([
            'email' => 'approved@example.com',
            'approved_at' => now(),
        ]);

        $this->artisan('channels:send-welcome')
            ->expectsOutput('Keine passenden Kanäle gefunden.')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function testSendsMailsToApprovedChannelsWhenForceIsUsed(): void
    {
        Mail::fake();

        $channel = Channel::factory()->create([
            'email' => 'approved@example.com',
            'approved_at' => now(),
        ]);

        $this->artisan('channels:send-welcome --force')
            ->expectsOutputToContain('📬 Sende Willkommens-Mail(s) an 1 Kanal(e)...')
            ->expectsOutputToContain('Versand abgeschlossen.')
            ->assertExitCode(0);

        Mail::assertQueued(
            ChannelWelcomeMail::class,
            static function ($mail) use ($channel) {
                return $mail->hasTo($channel->email);
            });
    }

    public function testCanTargetSpecificChannelById(): void
    {
        Mail::fake();

        $channel = Channel::factory()->create([
            'name' => 'Targeted',
            'email' => 'target@example.com',
        ]);

        Channel::factory()->create(); // weiterer Channel

        $this->artisan('channels:send-welcome '.$channel->id)
            ->expectsOutputToContain('📬 Sende Willkommens-Mail(s) an 1 Kanal(e)...')
            ->assertExitCode(0);

        Mail::assertQueued(
            ChannelWelcomeMail::class,
            static fn($mail) => $mail->hasTo('target@example.com')
        );
    }

    public function testCanTargetSpecificChannelByEmail(): void
    {
        Mail::fake();

        $channel = Channel::factory()->create([
            'name' => 'Target by Mail',
            'email' => 'special@example.com',
        ]);

        Channel::factory()->create(); // weiterer Channel

        $this->artisan('channels:send-welcome special@example.com')
            ->expectsOutputToContain('📬 Sende Willkommens-Mail(s) an 1 Kanal(e)...')
            ->assertExitCode(0);

        Mail::assertQueued(
            ChannelWelcomeMail::class,
            static fn($mail) => $mail->hasTo($channel->email)
        );
    }
}