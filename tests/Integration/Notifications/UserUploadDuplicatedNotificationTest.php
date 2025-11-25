<?php

declare(strict_types=1);

namespace Tests\Integration\Notifications;

use App\Models\User;
use App\Notifications\UserUploadDuplicatedNotification;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\DatabaseTestCase;

class UserUploadDuplicatedNotificationTest extends DatabaseTestCase
{
    public function testItSendsMailAndDatabaseNotifications(): void
    {
        Notification::fake();
        Mail::fake();

        $user = User::factory()->create();

        $notification = new UserUploadDuplicatedNotification(
            filename: 'video123.mp4',
            note: 'Dies ist ein Hinweis.'
        );

        $user->notify($notification);

        Notification::assertSentTo(
            $user,
            UserUploadDuplicatedNotification::class,
            fn($sent, array $channels) => in_array('mail', $channels, true)
                && in_array('database', $channels, true)
        );
    }

    public function testItStoresDatabaseNotificationCorrectly(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $notification = new UserUploadDuplicatedNotification(
            filename: 'video123.mp4',
            note: 'Mehrfacher Upload'
        );

        $user->notify($notification);

        $stored = DatabaseNotification::first();

        $this->assertNotNull($stored, 'Database notification was not stored.');

        $this->assertEquals(
            [
                'filename' => 'video123.mp4',
                'note' => 'Mehrfacher Upload',
            ],
            $stored->data
        );
    }

    public function testItSendsAFilamentDatabaseNotification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $notification = new UserUploadDuplicatedNotification(
            filename: 'clip.mov',
            note: null
        );

        $user->notify($notification);

        $filament = FilamentDatabaseNotification::first();

        $this->assertNotNull(
            $filament,
            'Filament database notification was not created.'
        );

        $this->assertStringContainsString('clip.mov', (string)$filament->body);
    }

    public function testMailMessageContainsExpectedContent(): void
    {
        $user = User::factory()->create();

        $notification = new UserUploadDuplicatedNotification(
            filename: 'testfile.mp4',
            note: 'Zusätzliche Notiz'
        );

        /** @var MailMessage $mail */
        $mail = $notification->toMail($user);

        $this->assertSame(
            'Upload verarbeitet: testfile.mp4',
            $mail->subject
        );

        $this->assertTrue(
            collect($mail->introLines)->contains('Dein Upload ist eine Doppeleinsendung!.')
        );

        $this->assertTrue(
            collect($mail->introLines)->contains('Zusätzliche Notiz')
        );
    }

    public function testArrayRepresentationIsCorrect(): void
    {
        $user = User::factory()->create();

        $notification = new UserUploadDuplicatedNotification(
            filename: 'abc.mov',
            note: 'Notiz'
        );

        $array = $notification->toArray($user);

        $this->assertEquals(
            [
                'filename' => 'abc.mov',
                'note' => 'Notiz',
            ],
            $array
        );
    }
}
