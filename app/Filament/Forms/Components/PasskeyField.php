<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use App\Exceptions\Passkey\PasskeyException;
use App\Models\User;
use App\Services\Passkeys\CeremonyService;
use Filament\Facades\Filament;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Renderless;

class PasskeyField extends Field
{
    protected string $view = 'filament.forms.components.passkey-field';

    protected string $purpose = 'challenge';

    protected ?User $passkeyUser = null;

    /** Configure the server-owned ceremony context; none of these values come from browser state. */
    public function ceremony(string $purpose, ?User $user): static
    {
        $this->purpose = $purpose;
        $this->passkeyUser = $user;

        return $this;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->required()->rules(['string', 'max:65536'])->label(__('passkeys.title'));
    }

    /**
     * Generate browser options only for this field's authenticated ceremony context.
     *
     * @return array{token: string, options: array<string, mixed>}
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function generateOptions(): array
    {
        if ($this->purpose === 'register') {
            abort_unless($this->passkeyUser?->is(Filament::auth()->user()), 403);
            $get = $this->getGetCallback();
            Validator::make([
                'name' => $get('name'),
                'currentPassword' => $get('currentPassword'),
            ], [
                'name' => ['required', 'string', 'max:255'],
                'currentPassword' => ['required', 'current_password:'.Filament::getAuthGuard()],
            ])->validate();
        }

        try {
            return app(CeremonyService::class)->begin($this->passkeyUser, Filament::getAuthGuard(), $this->purpose);
        } catch (PasskeyException $exception) {
            throw ValidationException::withMessages([$this->getStatePath() => $exception->getMessage()]);
        }
    }
}
