<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Enum\Guard\GuardEnum;
use App\Models\User;
use App\Notifications\UserInactivityReminderNotification;
use App\Repository\UserMailConfigRepository;
use App\Services\InactivityReminderService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\DatabaseTestCase;

final class InactivityReminderServiceTest extends DatabaseTestCase
{
    private InactivityReminderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->service = $this->app->make(InactivityReminderService::class);
    }

    public function testNotifiesEligibleUserAndMarksReminderSent(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(8),
        ]);

        $count = $this->service->notify(7);

        $this->assertSame(1, $count);
        Notification::assertSentTo($user, UserInactivityReminderNotification::class);
        $this->assertTrue($user->fresh()->last_login_reminder_sent_at->equalTo(Carbon::now()));
    }

    public function testDoesNotNotifyRecentlyActiveUser(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(2),
        ]);

        $count = $this->service->notify(7);

        $this->assertSame(0, $count);
        Notification::assertNotSentTo($user, UserInactivityReminderNotification::class);
    }

    public function testStillMarksReminderSentWhenUserOptedOut(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(8),
        ]);
        app(UserMailConfigRepository::class)->setForUser(
            $user,
            UserInactivityReminderNotification::class,
            false
        );

        $this->service->notify(7);

        $this->assertTrue($user->fresh()->last_login_reminder_sent_at->equalTo(Carbon::now()));
    }
}
