<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\Auth;

use App\Filament\Admin\Pages\Auth\EditProfile as BaseEditProfile;
use App\Filament\Standard\Components\TeamSettingsSection;
use App\Http\Middleware\SetDefaultTenant;
use App\Models\Team;
use App\Models\User;
use BezhanSalleh\FilamentShield\Middleware\SyncShieldTenant;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Render the profile inside the panel layout, scoped to the user's default team.
 */
class EditProfile extends BaseEditProfile
{
    /**
     * @var array<class-string>
     */
    protected static string|array $routeMiddleware = [
        SetDefaultTenant::class,
        SyncShieldTenant::class,
    ];

    /**
     * Fall back to the compact layout when the user has no team to build the navigation for.
     */
    public static function isSimple(): bool
    {
        $user = Filament::auth()->user();

        return $user === null || Filament::getUserDefaultTenant($user) === null;
    }

    /**
     * Add what the team of this person decides for itself, which only its owner may change.
     */
    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);
        $section = app(TeamSettingsSection::class)->make($this->ownedTeam());

        if ($section === null) {
            return $schema;
        }

        return $schema->components([...$schema->getComponents(), $section]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $team = $this->ownedTeam();
        if ($team !== null) {
            $data[TeamSettingsSection::STATE_PATH] = app(TeamSettingsSection::class)->state($team);
        }

        return parent::mutateFormDataBeforeFill($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model|User $record, array $data): Model
    {
        $chosen = (array)($data[TeamSettingsSection::STATE_PATH] ?? []);
        unset($data[TeamSettingsSection::STATE_PATH]);

        $team = $this->ownedTeam();
        if ($team !== null) {
            app(TeamSettingsSection::class)->save($team, $chosen);
        }

        return parent::handleRecordUpdate($record, $data);
    }

    /**
     * The current team, but only for the person who owns it.
     */
    private function ownedTeam(): ?Team
    {
        $team = Filament::getTenant();
        $user = Filament::auth()->user();

        if (!$team instanceof Team || $user === null) {
            return null;
        }

        return (int)$team->getAttribute('owner_id') === (int)$user->getKey() ? $team : null;
    }
}
