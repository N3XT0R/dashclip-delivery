<?php

declare(strict_types=1);

namespace App\Services\Channel;

use App\Events\Channel\ChannelAccessRequested;
use App\Facades\Activity;
use App\Models\ChannelApplication as ChannelApplicationModel;
use App\Models\User;
use App\Repository\ChannelRepository;
use App\Services\ChannelService;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class ChannelApplicationService
{
    public function __construct(private ChannelService $channelService, private ChannelRepository $channelRepository)
    {
    }

    /**
     * Approve a channel application.
     * @param ChannelApplicationModel $channelApplication
     * @param User|null $user
     * @throws Throwable
     */
    public function approveChannelApplication(
        ChannelApplicationModel $channelApplication,
        ?User $user = null
    ): void {
        DB::beginTransaction();

        try {
            $applicant = $channelApplication->user;
            $isNewChannel = $channelApplication->isNewChannel();

            if ($isNewChannel) {
                $channel = $this->channelService
                    ->createNewChannelByChannelApplication($channelApplication);
            } else {
                $channel = $channelApplication->channel;
            }

            if (!$this->channelRepository->assignUserToChannel($applicant, $channel)) {
                throw new \RuntimeException('Failed to assign user to channel.');
            }

            if (!$isNewChannel) {
                event(
                    new ChannelAccessRequested(
                        $channelApplication,
                        $applicant,
                        $channel
                    )
                );
            }

            if ($user) {
                Activity::createActivityLog(
                    'channel_applications',
                    $user,
                    'approved_channel_application',
                    [
                        'channel_application_id' => $channelApplication->getKey(),
                        'channel_id' => $channel->getKey(),
                        'is_new_channel' => $isNewChannel,
                        'applicant_user_id' => $applicant->getKey(),
                    ]
                );
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
