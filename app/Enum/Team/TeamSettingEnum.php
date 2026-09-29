<?php

declare(strict_types=1);

namespace App\Enum\Team;

use App\Enum\ConfigTypeEnum;

/**
 * The settings a team may keep, together with their type and what applies when nothing was chosen.
 */
enum TeamSettingEnum: string
{
    case CENSOR_LICENSE_PLATES = 'censor_license_plates';
    case CENSOR_FACES = 'censor_faces';

    public function type(): ConfigTypeEnum
    {
        return match ($this) {
            self::CENSOR_LICENSE_PLATES, self::CENSOR_FACES => ConfigTypeEnum::BOOL,
        };
    }

    /**
     * @return bool|int|string|null what applies while the team has not chosen
     */
    public function default(): bool|int|string|null
    {
        return match ($this) {
            // on by default: a team that never thought about it is better off with blurred plates
            self::CENSOR_LICENSE_PLATES => true,
            self::CENSOR_FACES => false,
        };
    }

    /**
     * Whether the setting is offered in the profile. A setting is only shown once the feature
     * behind it does something, so nobody switches on what nobody acts upon.
     */
    public function isOffered(): bool
    {
        return match ($this) {
            self::CENSOR_LICENSE_PLATES => true,
            // faces are not measured yet, so nothing acts upon this one
            self::CENSOR_FACES => false,
        };
    }

    public function label(): string
    {
        return __('team_settings.' . $this->value . '.label');
    }

    public function description(): string
    {
        return __('team_settings.' . $this->value . '.description');
    }

    /**
     * @return array<int, self>
     */
    public static function offered(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $case): bool => $case->isOffered()));
    }
}
