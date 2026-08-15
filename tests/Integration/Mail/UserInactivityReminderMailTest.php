<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Mail\UserInactivityReminderMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\DatabaseTestCase;

final class UserInactivityReminderMailTest extends DatabaseTestCase
{
    public function testEnvelopeSubjectIsSet(): void
    {
        $user = User::factory()->create();
        $lastLoginAt = Carbon::now()->subDays(10);

        $mail = new UserInactivityReminderMail($user, $lastLoginAt);

        $this->assertSame(
            __('mails.user_inactivity_reminder.subject'),
            $mail->envelope()->subject
        );
    }

    public function testViewDataContainsExpectedKeys(): void
    {
        $user = User::factory()->create();
        $lastLoginAt = Carbon::now()->subDays(10);

        $mail = new UserInactivityReminderMail($user, $lastLoginAt);
        $data = $mail->content()->with;

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('lastLoginAt', $data);
        $this->assertArrayHasKey('loginUrl', $data);
        $this->assertTrue($data['user']->is($user));
        $this->assertTrue($data['lastLoginAt']->equalTo($lastLoginAt));
    }

    public function testRenderedBodyMentionsProfileOptOut(): void
    {
        $user = User::factory()->create();
        $lastLoginAt = Carbon::now()->subDays(10);

        $mail = new UserInactivityReminderMail($user, $lastLoginAt);
        $html = $mail->render();

        $this->assertStringContainsString(
            __('mails.user_inactivity_reminder.opt_out_hint'),
            $html
        );
    }
}
