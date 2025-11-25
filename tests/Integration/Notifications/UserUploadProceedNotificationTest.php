<?php

declare(strict_types=1);

namespace Tests\Integration\Notifications;

use App\Models\User;
use App\Notifications\UserUploadProceedNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Tests\DatabaseTestCase;

class UserUploadProceedNotificationTest extends DatabaseTestCase
{
    public function testItSendsMailAndDatabaseNotifications(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $notification = new UserUploadProceedNotification(
            filename: 'video123.mp4',
            note: 'Dies ist ein Hinweis.'
        );

        $user->notify($notification);

        // Laravel Standard Notification
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => 'App\\Notifications\\UserUploadProceedNotification',
        ]);

        // Filament Notification
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => 'Filament\\Notifications\\DatabaseNotification',
        ]);
    }

    public function testItStoresLaravelDatabaseNotificationCorrectly(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $user->notify(
            new UserUploadProceedNotification(
                filename: 'video123.mp4',
                note: 'Mehrfacher Upload'
            )
        );

        // Filament erzeugt auch eine DB-Notification, aber wir suchen gezielt DEINE
        $stored = DatabaseNotification::where(
            'type',
            'App\\Notifications\\UserUploadProceedNotification'
        )->first();

        $this->assertNotNull($stored);

        $this->assertEquals(
            [
                'filename' => 'video123.mp4',
                'note' => 'Mehrfacher Upload',
            ],
            $stored->data
        );
    }

    public function testItStoresFilamentTriggeredDatabaseNotification(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $user->notify(
            new UserUploadProceedNotification(
                filename: 'clip.mov',
                note: null
            )
        );

        // Filament Notification Entry
        $this->assertDatabaseHas('notifications', [
            'type' => 'Filament\\Notifications\\DatabaseNotification',
            'notifiable_id' => $user->id,
        ]);

        // Payload prüfen
        $this->assertDatabaseHas('notifications', [
            'data->title' => 'Upload verarbeitet',
        ]);

        $this->assertDatabaseHas('notifications', [
            'data->body' => 'Die Datei **clip.mov** wurde erfolgreich bearbeitet.',
        ]);
    }

    public function testMailMessageContainsExpectedContent(): void
    {
        $user = User::factory()->create();

        $notification = new UserUploadProceedNotification(
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
            collect($mail->introLines)
                ->contains('Dein Upload wurde erfolgreich verarbeitet.')
        );

        $this->assertTrue(
            collect($mail->introLines)
                ->contains('Zusätzliche Notiz')
        );
    }

    public function testArrayRepresentationIsCorrect(): void
    {
        $user = User::factory()->create();

        $notification = new UserUploadProceedNotification(
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
