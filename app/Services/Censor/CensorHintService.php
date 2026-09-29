<?php

declare(strict_types=1);

namespace App\Services\Censor;

use App\Enum\Team\TeamSettingEnum;
use App\Models\Team;
use App\Repository\TeamSettingRepository;

/**
 * Answers whether the videos of a team are blurred, so the upload can say so.
 */
readonly class CensorHintService
{
    public function __construct(
        private TeamSettingRepository $settings,
        private VideoCensorInterface $censor,
    ) {
    }

    /**
     * @param Team|null $team the team the upload belongs to
     * @return bool whether plates of this upload will be blurred
     */
    public function appliesTo(?Team $team): bool
    {
        if ($team === null || !$this->censor->isAvailable()) {
            return false;
        }

        return (bool)$this->settings->get($team, TeamSettingEnum::CENSOR_LICENSE_PLATES);
    }
}
