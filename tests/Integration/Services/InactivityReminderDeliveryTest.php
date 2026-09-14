<?php

namespace Tests\Integration\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\DatabaseTestCase;

class InactivityReminderDeliveryTest extends DatabaseTestCase
{
    public function testCommandDeliversReminderForOldAccountWithoutLoginAndRespectsCooldown(): void
    {
        config(['queue.default' => 'sync', 'mail.default' => 'array']);
        $user = User::factory()->standard()->create([
            'created_at' => now()->subYear(),
            'last_login_at' => null,
        ]);

        $this->artisan('notify:inactive-users')->assertSuccessful();
        $this->artisan('notify:inactive-users')->assertSuccessful();

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertSame($user->email, $messages->first()->getOriginalMessage()->getTo()[0]->getAddress());
        $this->assertStringContainsString(
            e(__('mails.user_inactivity_reminder.body_without_login', ['app' => config('app.name')])),
            $messages->first()->getOriginalMessage()->getHtmlBody(),
        );
        $this->assertNull($user->fresh()->last_login_at);
        $this->assertNotNull($user->fresh()->last_login_reminder_sent_at);
    }

    public function testCommandDeliversReminderThroughRealMailChannel(): void
    {
        config(['queue.default' => 'sync', 'mail.default' => 'array']);
        $user = User::factory()->standard()->create([
            'last_login_at' => now()->subDays(8),
        ]);

        $this->artisan('notify:inactive-users')->assertSuccessful();

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertSame($user->email, $messages->first()->getOriginalMessage()->getTo()[0]->getAddress());
        $this->assertNotNull($user->fresh()->last_login_reminder_sent_at);
    }
}
