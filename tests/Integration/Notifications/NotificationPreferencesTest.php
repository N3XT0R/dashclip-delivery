<?php

declare(strict_types=1);

namespace Tests\Integration\Notifications;

use App\Models\User;
use App\Notifications\UserInactivityReminderNotification;
use App\Notifications\UserUploadDuplicatedNotification;
use App\Notifications\UserUploadProceedNotification;
use App\Repository\UserMailConfigRepository;
use Illuminate\Support\Carbon;
use Tests\DatabaseTestCase;

final class NotificationPreferencesTest extends DatabaseTestCase
{
    public function testMailChannelIsRemovedWhenOptedOut(): void
    {
        $user = User::factory()->create();
        $repository = app(UserMailConfigRepository::class);
        $repository->setForUser($user, UserUploadDuplicatedNotification::class, false);

        $notification = new UserUploadDuplicatedNotification('example.mov');

        $channels = $notification->via($user);

        $this->assertNotContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function testMailChannelRespectsDefaultOptIn(): void
    {
        $user = User::factory()->create();
        $notification = new UserUploadProceedNotification('example.mov');

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function testInactivityReminderMailChannelIsRemovedWhenOptedOut(): void
    {
        $user = User::factory()->create();
        $repository = app(UserMailConfigRepository::class);
        $repository->setForUser($user, UserInactivityReminderNotification::class, false);

        $notification = new UserInactivityReminderNotification(Carbon::now()->subDays(10));

        $channels = $notification->via($user);

        $this->assertNotContains('mail', $channels);
    }

    public function testInactivityReminderMailChannelRespectsDefaultOptIn(): void
    {
        $user = User::factory()->create();
        $notification = new UserInactivityReminderNotification(Carbon::now()->subDays(10));

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
    }
}
