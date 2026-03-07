<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChannelApplication;
use App\Models\User;
use App\Models\Video;
use App\Notifications\ChannelAccessApprovedNotification;
use App\Notifications\UserUploadDuplicatedNotification;
use App\Notifications\UserUploadProceedNotification;

class NotificationService
{
    /**
     * Send channel access approved notification to the user.
     * @param ChannelApplication $channelApplication
     * @return void
     */
    public function notifyChannelAccessApproved(ChannelApplication $channelApplication): void
    {
        $user = $channelApplication->user;
        $user->notify(new ChannelAccessApprovedNotification($channelApplication));
    }

    /**
     * Send duplicated upload notification to the user.
     * @param Video $video
     * @param User $user
     * @return void
     */
    public function notifyDuplicatedUpload(User $user, Video $video): void
    {
        $user->notify(
            new UserUploadDuplicatedNotification(
                filename: $video->original_name,
                note: __('user_upload_duplicated.body', ['filename' => $video->original_name])
            )
        );
    }

    /**
     * Send upload proceed notification to the user.
     * @param User $user
     * @param Video $video
     * @return void
     */
    public function notifyUserUploadComplete(User $user, Video $video): void
    {
        $user->notify(
            new UserUploadProceedNotification(
                filename: $video->original_name,
                note: 'Alles erfolgreich abgeschlossen.'
            )
        );
    }
}
