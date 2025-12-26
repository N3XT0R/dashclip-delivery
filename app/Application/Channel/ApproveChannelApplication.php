<?php

declare(strict_types=1);

namespace App\Application\Channel;

use App\Events\Channel\ChannelAccessRequested;
use App\Facades\Activity;
use App\Models\ChannelApplication as ChannelApplicationModel;
use App\Models\User;
use App\Repository\ChannelRepository;
use App\Services\ChannelService;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class ApproveChannelApplication
{
    public function __construct(
        private ChannelService $channelService,
        private ChannelRepository $channelRepository,
    ) {
    }

    public function handle(
        ChannelApplicationModel $application,
        ?User $approvedBy
    ) {
        DB::beginTransaction();

        try {
            $applicant = $application->user;
            $isNewChannel = $application->isNewChannel();

            if ($isNewChannel) {
                $channel = $this->channelService
                    ->createNewChannelByChannelApplication($application);
            } else {
                $channel = $application->channel;
            }

            if (!$this->channelRepository->assignUserToChannel($applicant, $channel, $isNewChannel)) {
                throw new \RuntimeException('Failed to assign user to channel.');
            }

            if (!$isNewChannel) {
                DB::afterCommit(static fn() => event(
                    new ChannelAccessRequested(
                        channelApplication: $application
                    )
                ));
            }

            if ($approvedBy) {
                Activity::createActivityLog(
                    'channel_applications',
                    $approvedBy,
                    'approved_channel_application',
                    [
                        'channel_application_id' => $application->getKey(),
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
