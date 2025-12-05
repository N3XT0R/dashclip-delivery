<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Facades\NotificationDiscovery;
use App\Models\User;
use App\Repository\UserMailConfigRepository;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditProfile extends BaseEditProfile
{
    protected static bool $isScopedToTenant = false;

    private const CHECKBOX_NAMESPACE = 'notifications.mail.types.';


    public function form(Schema $schema): Schema
    {
        /**
         * @var TextInput $nameComponent
         */
        $nameComponent = $this->getNameFormComponent();
        return $schema
            ->components([
                $nameComponent
                    ->unique(),
                $this->getSubmittedNameComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getNotificationComponent(),
            ]);
    }

    protected function getSubmittedNameComponent(): Component
    {
        return TextInput::make('submitted_name')
            ->required()
            ->label('Einsender-Name')
            ->unique('users')
            ->maxLength(255);
    }


    protected function getNotificationComponent(): Component
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        $repo = app(UserMailConfigRepository::class);

        return Section::make(__('notifications.mail.title'))
            ->collapsed()
            ->schema(
                collect(NotificationDiscovery::list())
                    ->map(function ($class) use ($user, $repo) {
                        $key = self::CHECKBOX_NAMESPACE.$class;
                        $isAllowed = $repo->isAllowed($user, $class);

                        return Checkbox::make($key)
                            ->translateLabel()
                            ->label($key)
                            ->formatStateUsing(fn() => ($isAllowed));
                    })->toArray()
            );
    }

    protected function handleRecordUpdate(Model|User $record, array $data): Model
    {
        $repo = app(UserMailConfigRepository::class);

        $types = data_get($data, 'notifications.mail.types', []);

        foreach (NotificationDiscovery::list() as $class) {
            if (array_key_exists($class, $types)) {
                $repo->setForUser($record, $class, $types[$class]);
            }
        }

        unset($data['notifications']);

        return parent::handleRecordUpdate($record, $data);
    }
}