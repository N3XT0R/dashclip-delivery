<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Listeners\RecordUserLastLoginListener;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;
use Tests\DatabaseTestCase;

final class RecordUserLastLoginListenerTest extends DatabaseTestCase
{
    public function testHandleRecordsLoginForUser(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = User::factory()->create();
        $user->markInactivityReminderSent();
        $this->assertNotNull($user->fresh()->last_login_reminder_sent_at);

        $event = new Login('standard', $user, false);
        (new RecordUserLastLoginListener())->handle($event);

        $fresh = $user->fresh();
        $this->assertTrue($fresh->last_login_at->equalTo(Carbon::now()));
        $this->assertNull($fresh->last_login_reminder_sent_at);
    }

    public function testHandleIgnoresNonUserNotifiables(): void
    {
        $notifiable = new class {
            public $id = 999;
        };

        $event = new Login('standard', $notifiable, false);

        // Must not throw
        (new RecordUserLastLoginListener())->handle($event);

        $this->assertTrue(true);
    }

    public function testLoginEventDispatchTriggersListener(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = User::factory()->create();

        event(new Login('standard', $user, false));

        $this->assertTrue($user->fresh()->last_login_at->equalTo(Carbon::now()));
    }
}
