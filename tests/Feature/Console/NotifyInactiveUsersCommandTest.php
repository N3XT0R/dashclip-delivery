<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enum\Guard\GuardEnum;
use App\Models\User;
use App\Notifications\UserInactivityReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Tests\DatabaseTestCase;

final class NotifyInactiveUsersCommandTest extends DatabaseTestCase
{
    public function testCommandNotifiesEligibleUsers(): void
    {
        Notification::fake();

        $eligible = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(8),
        ]);
        $active = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDay(),
        ]);

        $this->artisan('notify:inactive-users')
            ->assertExitCode(Command::SUCCESS);

        Notification::assertSentTo($eligible, UserInactivityReminderNotification::class);
        Notification::assertNotSentTo($active, UserInactivityReminderNotification::class);
    }

    public function testCommandRespectsCustomDaysOption(): void
    {
        Notification::fake();

        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(4),
        ]);

        $this->artisan('notify:inactive-users --days=3')
            ->assertExitCode(Command::SUCCESS);

        Notification::assertSentTo($user, UserInactivityReminderNotification::class);
    }
}
