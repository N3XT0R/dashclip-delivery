<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\Auth;

use App\Enum\Team\TeamSettingEnum;
use App\Models\Team;
use App\Repository\TeamSettingRepository;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Tenancy\EditTenantProfile as BaseProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class EditTenantProfile extends BaseProfile
{
    private const SETTINGS_PATH = 'team_settings';

    public static function getLabel(): string
    {
        return __('filament.standard.profile.label');
    }

    public function form(Schema $schema): Schema
    {
        $components = [
            TextInput::make('name')
                ->label(__('filament.admin.labels.name'))
                ->required()
                ->maxLength(255),
        ];

        $settings = $this->settingsSection();
        if ($settings !== null) {
            $components[] = $settings;
        }

        return $schema->components($components);
    }

    /**
     * The settings this team may choose, or nothing while none is offered.
     */
    private function settingsSection(): ?Section
    {
        $offered = TeamSettingEnum::offered();
        if ($offered === []) {
            return null;
        }

        $repository = app(TeamSettingRepository::class);
        /** @var Team $team */
        $team = $this->tenant;

        return Section::make(__('team_settings.title'))
            ->description(__('team_settings.description'))
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->schema(array_map(
                fn (TeamSettingEnum $setting): Toggle => Toggle::make(
                    self::SETTINGS_PATH . '.' . $setting->value
                )
                    ->label($setting->label())
                    ->helperText($setting->description())
                    ->default((bool)$repository->get($team, $setting)),
                $offered,
            ));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $repository = app(TeamSettingRepository::class);
        /** @var Team $team */
        $team = $this->tenant;

        foreach (TeamSettingEnum::offered() as $setting) {
            $data[self::SETTINGS_PATH][$setting->value] = (bool)$repository->get($team, $setting);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $repository = app(TeamSettingRepository::class);
        $chosen = (array)($data[self::SETTINGS_PATH] ?? []);
        unset($data[self::SETTINGS_PATH]);

        /** @var Team $record */
        foreach (TeamSettingEnum::offered() as $setting) {
            if (array_key_exists($setting->value, $chosen)) {
                $repository->set($record, $setting, (bool)$chosen[$setting->value]);
            }
        }

        return parent::handleRecordUpdate($record, $data);
    }
}
