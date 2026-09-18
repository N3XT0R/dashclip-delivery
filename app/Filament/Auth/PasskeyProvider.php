<?php

namespace App\Filament\Auth;

use App\Exceptions\Passkey\PasskeyException;
use App\Filament\Forms\Components\PasskeyField;
use App\Models\User;
use App\Repository\PasskeyRepository;
use App\Services\Passkeys\CeremonyService;
use Closure;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

class PasskeyProvider implements MultiFactorAuthenticationProvider
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'passkey';
    }

    public function getLoginFormLabel(): string
    {
        return __('passkeys.title');
    }

    public function isEnabled(Authenticatable $user): bool
    {
        return $user instanceof User && app(PasskeyRepository::class)->hasForUser($user);
    }

    /** @return array<Component|Action> */
    public function getManagementSchemaComponents(): array
    {
        $user = Filament::auth()->user();
        $repository = app(PasskeyRepository::class);

        return [
            Actions::make([
                $this->registerAction($user),
                $this->renameAction($user),
                $this->deleteAction($user),
            ])
                ->label(__('passkeys.title'))
                ->belowContent(__('passkeys.description'))
                ->afterLabel(fn (): Text => Text::make((string) $repository->forUser($user)->count())->badge()),
            View::make('filament.auth.passkeys')
                ->viewData(fn (): array => ['passkeys' => $repository->forUser($user)]),
        ];
    }

    /**
     * Require a verified assertion in Filament's own validation flow.
     *
     * @return array<Component|Action>
     */
    public function getChallengeFormComponents(Authenticatable $user): array
    {
        return [
            PasskeyField::make('assertion')->ceremony('challenge', $user)
                ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    try {
                        app(CeremonyService::class)->authenticate($user, Filament::getAuthGuard(), (string) $value);
                    } catch (PasskeyException $exception) {
                        $fail($exception->getMessage());
                    }
                }),
        ];
    }

    private function registerAction(User $user): Action
    {
        return Action::make('registerPasskey')
            ->label(__('passkeys.add'))->icon(Heroicon::OutlinedPlus)->button()
            ->schema([
                TextInput::make('name')->label(__('passkeys.name'))->required()->maxLength(255),
                $this->passwordField(),
                PasskeyField::make('attestation')->ceremony('register', $user),
            ])
            ->action(function (array $data, Schema $schema) use ($user): void {
                try {
                    app(CeremonyService::class)->register($user, Filament::getAuthGuard(), $data['attestation'], $data['name']);
                } catch (PasskeyException $exception) {
                    throw ValidationException::withMessages([$schema->getStatePath().'.attestation' => $exception->getMessage()]);
                }
                Notification::make()->title(__('passkeys.added'))->success()->send();
            })->rateLimit(5);
    }

    private function renameAction(User $user): Action
    {
        return Action::make('renamePasskey')->label(__('passkeys.rename'))->color('gray')
            ->visible(fn (): bool => $this->isEnabled($user))
            ->schema([
                $this->passkeySelect($user),
                TextInput::make('name')->label(__('passkeys.name'))->required()->maxLength(255),
            ])
            ->action(function (array $data) use ($user): void {
                app(PasskeyRepository::class)->rename($user, (int) $data['passkey'], $data['name']);
                Notification::make()->title(__('passkeys.renamed'))->success()->send();
            });
    }

    private function deleteAction(User $user): Action
    {
        return Action::make('deletePasskey')->label(__('passkeys.delete'))->color('danger')
            ->visible(fn (): bool => $this->isEnabled($user))
            ->modalDescription(__('passkeys.delete_description'))
            ->schema([$this->passkeySelect($user), $this->passwordField()])
            ->action(function (array $data) use ($user): void {
                app(PasskeyRepository::class)->delete($user, (int) $data['passkey']);
                Notification::make()->title(__('passkeys.deleted'))->success()->send();
            })->rateLimit(5);
    }

    private function passkeySelect(User $user): Select
    {
        return Select::make('passkey')->label(__('passkeys.title'))->required()
            ->options(fn (): array => app(PasskeyRepository::class)->forUser($user)->pluck('name', 'id')->all());
    }

    private function passwordField(): TextInput
    {
        return TextInput::make('currentPassword')->label(__('passkeys.current_password'))
            ->password()->autocomplete('current-password')->required()->currentPassword(Filament::getAuthGuard());
    }
}
