<?php

namespace App\Services\Passkeys;

use App\Exceptions\Passkey\PasskeyException;
use App\Models\Passkey;
use App\Models\User;
use App\Repository\PasskeyRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;
use Throwable;

class CeremonyService
{
    public function __construct(
        private readonly PasskeyRepository $passkeys,
        private readonly GeneratePasskeyRegisterOptionsAction $registrationOptions,
        private readonly GeneratePasskeyAuthenticationOptionsAction $authenticationOptions,
        private readonly StorePasskeyAction $storePasskey,
        private readonly FindPasskeyToAuthenticateAction $findPasskey,
    ) {}

    /**
     * Issue a five-minute challenge bound to the session, guard, purpose and account.
     *
     * @return array{token: string, options: array<string, mixed>}
     * @throws PasskeyException
     */
    public function begin(?User $user, string $guard, string $purpose): array
    {
        $rateKey = 'passkeys:options:'.hash('sha256', request()->ip().'|'.session()->getId());
        if (RateLimiter::tooManyAttempts($rateKey, 15)) {
            throw new PasskeyException(__('passkeys.rate_limited'));
        }
        RateLimiter::hit($rateKey);

        if (! in_array($purpose, ['register', 'challenge', 'login'], true) || ($purpose !== 'login' && $user === null)) {
            throw new PasskeyException(__('passkeys.invalid'));
        }

        $options = json_decode($purpose === 'register'
            ? $this->registrationOptions->execute($user)
            : $this->authenticationOptions->execute(), true, flags: JSON_THROW_ON_ERROR);

        if ($purpose === 'register') {
            $options['authenticatorSelection']['userVerification'] = 'required';
            $options['excludeCredentials'] = $this->descriptors($user);
        } else {
            $options['userVerification'] = 'required';
            $options['allowCredentials'] = $user === null ? [] : $this->descriptors($user);
        }
        $options['timeout'] = 60000;
        $token = Str::random(64);
        Cache::put($this->cacheKey($token, $user, $guard, $purpose), $options, now()->addMinutes(5));

        return ['token' => $token, 'options' => $options];
    }

    /** Persist a verified credential without replacing other passkeys. @throws PasskeyException */
    public function register(User $user, string $guard, string $payload, string $name): Passkey
    {
        [$credential, $options] = $this->consume($payload, $user, $guard, 'register');

        try {
            return $this->storePasskey->execute($user, $credential, $options, config('passkeys.relying_party.id'), ['name' => $name]);
        } catch (Throwable $exception) {
            throw new PasskeyException(__('passkeys.invalid'), previous: $exception);
        }
    }

    /** Verify the signature and user verification for the requested account. @throws PasskeyException */
    public function authenticate(?User $user, string $guard, string $payload): User
    {
        [$credential, $options] = $this->consume($payload, $user, $guard, $user === null ? 'login' : 'challenge');

        try {
            $passkey = $this->findPasskey->execute($credential, $options);
        } catch (Throwable $exception) {
            throw new PasskeyException(__('passkeys.invalid'), previous: $exception);
        }

        $owner = $passkey?->authenticatable;
        if (! $owner instanceof User || ($user !== null && ! $owner->is($user))) {
            throw new PasskeyException(__('passkeys.invalid'));
        }

        return $owner;
    }

    /** @return list<array{type: string, id: string}> */
    private function descriptors(User $user): array
    {
        return $this->passkeys->forUser($user)->map(fn (Passkey $passkey): array => [
            'type' => 'public-key',
            'id' => rtrim(strtr(base64_encode($passkey->data->publicKeyCredentialId), '+/', '-_'), '='),
        ])->all();
    }

    /**
     * Atomically consume a challenge, including unsuccessful verification attempts.
     *
     * @return array{string, string}
     * @throws PasskeyException
     */
    private function consume(string $payload, ?User $user, string $guard, string $purpose): array
    {
        $data = json_decode($payload, true);
        if (! is_array($data) || ! is_string($data['token'] ?? null) || strlen($data['token']) !== 64
            || ! is_array($data['credential'] ?? null)) {
            throw new PasskeyException(__('passkeys.invalid'));
        }

        $key = $this->cacheKey($data['token'], $user, $guard, $purpose);
        $options = Cache::lock($key.':lock', 10)->get(fn () => Cache::pull($key));
        if (! is_array($options)) {
            throw new PasskeyException(__('passkeys.expired'));
        }

        return [json_encode($data['credential'], JSON_THROW_ON_ERROR), json_encode($options, JSON_THROW_ON_ERROR)];
    }

    private function cacheKey(string $token, ?User $user, string $guard, string $purpose): string
    {
        return 'passkeys:challenge:'.hash('sha256', implode('|', [session()->getId(), $guard, $purpose, $user?->getKey(), $token]));
    }
}
