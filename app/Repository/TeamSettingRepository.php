<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\Team\TeamSettingEnum;
use App\Models\Team;
use App\Models\TeamSetting;

/**
 * Reads and writes the settings a team keeps for itself.
 */
class TeamSettingRepository
{
    /**
     * @param Team $team
     * @param TeamSettingEnum $setting
     * @return bool|int|string|null the stored value, or what applies while the team has not chosen
     */
    public function get(Team $team, TeamSettingEnum $setting): bool|int|string|null
    {
        $row = TeamSetting::query()
            ->where('team_id', $team->getKey())
            ->where('key', $setting->value)
            ->first();

        return $row === null ? $setting->default() : $row->value;
    }

    public function set(Team $team, TeamSettingEnum $setting, bool|int|string|null $value): TeamSetting
    {
        return TeamSetting::query()->updateOrCreate(
            ['team_id' => $team->getKey(), 'key' => $setting->value],
            ['type' => $setting->type()->value, 'value' => $value],
        );
    }

    /**
     * Every setting of the team, including the ones it never chose.
     *
     * @param Team $team
     * @return array<string, bool|int|string|null>
     */
    public function all(Team $team): array
    {
        $stored = TeamSetting::query()
            ->where('team_id', $team->getKey())
            ->get()
            ->keyBy('key');

        $settings = [];
        foreach (TeamSettingEnum::cases() as $setting) {
            $settings[$setting->value] = $stored->has($setting->value)
                ? $stored->get($setting->value)->value
                : $setting->default();
        }

        return $settings;
    }
}
