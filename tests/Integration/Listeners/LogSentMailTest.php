<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Listeners\LogSentMail;
use App\Mail\UserWelcomeMail;
use App\Models\MailLog;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\DatabaseTestCase;

class LogSentMailTest extends DatabaseTestCase
{
    protected LogSentMail $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = $this->app->make(LogSentMail::class);
        MailLog::query()->delete();
    }

    public function testListenerCreatesMailLogEntryWhenMailIsSent(): void
    {
        // Arrange
        Config::set('mail.default', 'array');

        $user = User::factory()->create(['email' => 'user@example.com']);

        // Act
        \Mail::to($user->email)->send(new UserWelcomeMail($user, false, 'secret123'));

        // Assert
        $this->assertDatabaseCount('mail_logs', 1);

        $log = MailLog::first();
        $this->assertNotNull($log->message_id);
        $this->assertTrue(Str::isUuid($log->internal_id));
        $this->assertSame('user@example.com', $log->to);
        $this->assertNotEmpty($log->subject);
        $this->assertIsArray($log->meta);
        $this->assertArrayHasKey('headers', $log->meta);
        $this->assertArrayHasKey('content', $log->meta);
    }
}
