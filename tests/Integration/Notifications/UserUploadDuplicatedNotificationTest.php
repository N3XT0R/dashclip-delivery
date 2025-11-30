<?php

declare(strict_types=1);

namespace Tests\Integration\Notifications;

use App\Mail\UserUploadProceedMail;
use App\Models\User;
use App\Notifications\UserUploadDuplicatedNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;
use Tests\DatabaseTestCase;

class UserUploadDuplicatedNotificationTest extends DatabaseTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        \Bus::fake();
    }


    public function testItSendsMailAndDatabaseNotifications(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $notification = new UserUploadDuplicatedNotification(
            filename: 'video123.mp4',
            note: 'Dies ist ein Hinweis.'
        );

        $user->notify($notification);

        // Erwartete Notification 1: Laravel-Standardnotification
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => UserUploadDuplicatedNotification::class,
        ]);

        // Erwartete Notification 2: Filament-Notification
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => \Filament\Notifications\DatabaseNotification::class,
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

        // Finde Laravel-Notification, nicht die Filament-Notification
        $stored = DatabaseNotification::where('type', UserUploadDuplicatedNotification::class)->first();

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
            new UserUploadDuplicatedNotification(
                filename: 'clip.mov',
                note: null
            )
        );

        $this->assertDatabaseHas('notifications', [
            'type' => \Filament\Notifications\DatabaseNotification::class,
            'notifiable_id' => $user->id,
        ]);

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

        $notification = new UserUploadDuplicatedNotification(
            filename: 'testfile.mp4',
            note: 'Zusätzliche Notiz'
        );

        /** @var UserUploadProceedMail $mail */
        $mail = $notification->toMail($user);

        $this->assertSame(
            'Upload verarbeitet: testfile.mp4',
            $mail->subject
        );

        $html = $mail->render();
        $this->assertStringContainsString(
            'Dein Upload ist eine Doppeleinsendung!.',
            $html
        );

        $this->assertStringContainsString(
            'Zusätzliche Notiz',
            $html
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
