<?php

declare(strict_types=1);

namespace App\Filament\Standard\Components;

use App\Enum\Team\TeamSettingEnum;
use App\Models\Team;
use App\Repository\TeamSettingRepository;
use App\Services\Censor\VideoCensorInterface;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

/**
 * The settings a team chooses for itself, shown to the person who owns the team.
 */
readonly class TeamSettingsSection
{
    public const string STATE_PATH = 'team_settings';

    public function __construct(
        private TeamSettingRepository $settings,
        private VideoCensorInterface $censor,
    ) {
    }

    /**
     * @return array<int, TeamSettingEnum> the settings this team may choose right now
     */
    public function availableFor(?Team $team): array
    {
        if ($team === null) {
            return [];
        }

        return array_values(array_filter(
            TeamSettingEnum::offered(),
            fn (TeamSettingEnum $setting): bool => $this->isSupportedHere($setting),
        ));
    }

    /**
     * @return Section|null nothing when the team has no setting to choose
     */
    public function make(?Team $team): ?Section
    {
        $available = $this->availableFor($team);
        if ($available === [] || $team === null) {
            return null;
        }

        return Section::make(__('team_settings.title'))
            ->description(__('team_settings.description'))
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->schema(array_map(
                fn (TeamSettingEnum $setting): Toggle => Toggle::make(
                    self::STATE_PATH . '.' . $setting->value
                )
                    ->label($setting->label())
                    ->helperText($setting->description())
                    ->default((bool)$this->settings->get($team, $setting)),
                $available,
            ));
    }

    /**
     * @param Team $team
     * @return array<string, bool> the choices of the team, for filling the form
     */
    public function state(Team $team): array
    {
        $state = [];
        foreach ($this->availableFor($team) as $setting) {
            $state[$setting->value] = (bool)$this->settings->get($team, $setting);
        }

        return $state;
    }

    /**
     * @param Team $team
     * @param array<string, mixed> $chosen
     */
    public function save(Team $team, array $chosen): void
    {
        foreach ($this->availableFor($team) as $setting) {
            if (array_key_exists($setting->value, $chosen)) {
                $this->settings->set($team, $setting, (bool)$chosen[$setting->value]);
            }
        }
    }

    /**
     * Whether this installation can act on the setting at all. A team is not offered a switch that
     * nothing here would carry out, because its videos would get stuck instead of going out.
     */
    private function isSupportedHere(TeamSettingEnum $setting): bool
    {
        return match ($setting) {
            TeamSettingEnum::CENSOR_LICENSE_PLATES, TeamSettingEnum::CENSOR_FACES
                => $this->censor->isAvailable(),
        };
    }
}
