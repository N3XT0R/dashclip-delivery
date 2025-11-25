<?php

declare(strict_types=1);

namespace Tests\Integration\Notifications;

use App\Models\User;
use App\Notifications\UserUploadDuplicatedNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Tests\DatabaseTestCase;

class UserUploadDuplicatedNotificationTest extends DatabaseTestCase
{
    public function testItSendsMailAndDatabaseNotifications(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $notification = new UserUploadDuplicatedNotification(
            filename: 'video123.mp4',
            note: 'Dies ist ein Hinweis.'
        );

        $user->notify($notification);

        // Prüft, dass *beide* Channels aktiviert wurden
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => 'Illuminate\\Notifications\\DatabaseNotification',
        ]);
    }

    public function testItStoresLaravelDatabaseNotificationCorrectly(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $user->notify(
            new UserUploadDuplicatedNotification(
                filename: 'video123.mp4',
                note: 'Mehrfacher Upload'
            )
        );

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

    public function testItStoresFilamentTriggeredDatabaseNotification(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $user->notify(
            new UserUploadDuplicatedNotification(
                filename: 'clip.mov',
                note: null
            )
        );

        // Die Filament-Notification erzeugt *ebenfalls* eine Laravel-DB-Notification,
        // diesmal mit Filament-spezifischem Payload
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            // Laravel DatabaseNotification
            'type' => 'Illuminate\\Notifications\\DatabaseNotification',
        ]);

        // Prüfen, dass der Filament-Body enthalten ist
        $this->assertDatabaseHas('notifications', [
            'data->body' => 'Die Datei **clip.mov** wurde erfolgreich bearbeitet.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'data->title' => 'Upload verarbeitet',
        ]);
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
            collect($mail->introLines)
                ->contains('Dein Upload ist eine Doppeleinsendung!.')
        );

        $this->assertTrue(
            collect($mail->introLines)
                ->contains('Zusätzliche Notiz')
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
