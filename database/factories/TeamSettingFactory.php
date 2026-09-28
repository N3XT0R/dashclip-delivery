<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\Team\TeamSettingEnum;
use App\Models\Team;
use App\Models\TeamSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamSettingFactory extends Factory
{
    protected $model = TeamSetting::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'key' => TeamSettingEnum::CENSOR_LICENSE_PLATES->value,
            'type' => TeamSettingEnum::CENSOR_LICENSE_PLATES->type()->value,
            'value' => true,
        ];
    }

    public function forTeam(Team $team): static
    {
        return $this->state(fn () => ['team_id' => $team->getKey()]);
    }
}
