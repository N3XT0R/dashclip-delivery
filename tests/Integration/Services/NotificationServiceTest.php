<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\ChannelApplication;
use App\Models\User;
use App\Notifications\ChannelAccessApprovedNotification;
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
        // Arrange
        $user = User::factory()->create();

        $channelApplication = ChannelApplication::factory()->create([
            'user_id' => $user->getKey(),
        ]);

        // Act
        $this->notificationService->notifyChannelAccessApproved($channelApplication);

        // Assert
        Notification::assertSentTo(
            $user,
            ChannelAccessApprovedNotification::class,
            function (ChannelAccessApprovedNotification $notification) use ($channelApplication) {
                return $notification->channelApplication->is($channelApplication);
            }
        );
    }
}
