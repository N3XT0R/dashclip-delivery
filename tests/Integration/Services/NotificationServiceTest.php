<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\ChannelApplication;
use App\Models\User;
use App\Models\Video;
use App\Notifications\ChannelAccessApprovedNotification;
use App\Notifications\UserUploadDuplicatedNotification;
use App\Notifications\UserUploadProceedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Notification;
use Tests\DatabaseTestCase;

class NotificationServiceTest extends DatabaseTestCase
{
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->notificationService = $this->app->make(NotificationService::class);
    }

    public function testItSendsChannelAccessApprovedNotificationToUser(): void
    {
        $user = User::factory()->create();

        $channelApplication = ChannelApplication::factory()->create([
            'user_id' => $user->getKey(),
        ]);

        $this->notificationService->notifyChannelAccessApproved($channelApplication);

        Notification::assertSentTo(
            $user,
            ChannelAccessApprovedNotification::class,
            function (ChannelAccessApprovedNotification $notification) use ($channelApplication) {
                return $notification->channelApplication->is($channelApplication);
            }
        );
    }

    public function testItSendsDuplicatedUploadNotificationToUser(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create(['original_name' => 'duplicate.mp4']);

        $this->notificationService->notifyDuplicatedUpload($user, $video);

        Notification::assertSentTo(
            $user,
            UserUploadDuplicatedNotification::class,
            function (UserUploadDuplicatedNotification $notification) {
                return $notification->filename === 'duplicate.mp4';
            }
        );
    }

    public function testItSendsUploadProceedNotificationToUser(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create(['original_name' => 'proceed.mp4']);

        $this->notificationService->notifyUserVideoUploadProceeded($user, $video);

        Notification::assertSentTo(
            $user,
            UserUploadProceedNotification::class,
            function (UserUploadProceedNotification $notification) {
                return $notification->filename === 'proceed.mp4';
            }
        );
    }

    public function testDuplicatedUploadNotificationContainsExpectedNote(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create(['original_name' => 'clip.mp4']);

        $this->notificationService->notifyDuplicatedUpload($user, $video);

        Notification::assertSentTo(
            $user,
            UserUploadDuplicatedNotification::class,
            function (UserUploadDuplicatedNotification $notification) {
                return str_contains($notification->note, 'clip.mp4');
            }
        );
    }

    public function testUploadProceedNotificationContainsExpectedNote(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create(['original_name' => 'final.mp4']);

        $this->notificationService->notifyUserVideoUploadProceeded($user, $video);

        Notification::assertSentTo(
            $user,
            UserUploadProceedNotification::class,
            function (UserUploadProceedNotification $notification) {
                return str_contains($notification->note, 'final.mp4');
            }
        );
    }
}
