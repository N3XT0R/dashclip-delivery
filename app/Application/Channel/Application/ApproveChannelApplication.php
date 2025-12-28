<?php

declare(strict_types=1);

namespace App\Application\Channel\Application;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Events\Channel\ChannelAccessRequested;
use App\Models\ChannelApplication as ChannelApplicationModel;
use App\Models\User;
use App\Repository\ChannelRepository;
use App\Repository\RoleRepository;
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
    ): void {
        DB::beginTransaction();

        try {
            $applicant = $application->user;
            $isNewChannel = $application->isNewChannel();

            if ($isNewChannel) {
                $channel = $this->channelService
                    ->createNewChannelByChannelApplication($application);
                $roleRepository = app(RoleRepository::class);
                if (!$roleRepository->hasRole($applicant, RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD)) {
                    $roleRepository->giveRoleToUser($applicant, RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD);
                }
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
                activity('channel_applications')
                    ->event('approved_channel_application')
                    ->causedBy($approvedBy)
                    ->performedOn($application)
                    ->withProperties([
                        'channel_application_id' => $application->getKey(),
                        'channel_id' => $channel->getKey(),
                        'is_new_channel' => $isNewChannel,
                        'applicant_user_id' => $applicant->getKey(),
                    ]);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
