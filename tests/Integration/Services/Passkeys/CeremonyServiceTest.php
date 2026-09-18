<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Passkeys;

use App\Enum\Guard\GuardEnum;
use App\Exceptions\Passkey\PasskeyException;
use App\Models\User;
use App\Services\Passkeys\CeremonyService;
use Tests\DatabaseTestCase;
use Tests\Support\SoftwareAuthenticator;

final class CeremonyServiceTest extends DatabaseTestCase
{
    private CeremonyService $ceremonies;

    private SoftwareAuthenticator $authenticator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->startSession();
        $this->ceremonies = $this->app->make(CeremonyService::class);
        $rpId = (string) config('passkeys.relying_party.id');
        $this->authenticator = new SoftwareAuthenticator($rpId, 'https://'.$rpId);
    }

    public function testRegisteredPasskeySignsInWithoutKnownAccount(): void
    {
        $user = $this->registerPasskey();

        $challenge = $this->ceremonies->begin(null, GuardEnum::STANDARD->value, 'login');
        $payload = json_encode(['token' => $challenge['token'], 'credential' => $this->authenticator->get($challenge['options'])]);

        $this->assertTrue($this->ceremonies->authenticate(null, GuardEnum::STANDARD->value, $payload)->is($user));
    }

    public function testRegisteredPasskeyPassesSecondStepForItsAccount(): void
    {
        $user = $this->registerPasskey();

        $challenge = $this->ceremonies->begin($user, GuardEnum::STANDARD->value, 'challenge');
        $payload = json_encode(['token' => $challenge['token'], 'credential' => $this->authenticator->get($challenge['options'])]);

        $this->assertTrue($this->ceremonies->authenticate($user, GuardEnum::STANDARD->value, $payload)->is($user));
    }

    public function testPasskeyIsRejectedAsSecondStepForAnotherAccount(): void
    {
        $this->registerPasskey();
        $otherUser = User::factory()->standard()->create();

        $challenge = $this->ceremonies->begin($otherUser, GuardEnum::STANDARD->value, 'challenge');
        $payload = json_encode(['token' => $challenge['token'], 'credential' => $this->authenticator->get($challenge['options'])]);

        $this->expectException(PasskeyException::class);
        $this->ceremonies->authenticate($otherUser, GuardEnum::STANDARD->value, $payload);
    }

    private function registerPasskey(): User
    {
        $user = User::factory()->standard()->create();
        $challenge = $this->ceremonies->begin($user, GuardEnum::STANDARD->value, 'register');
        $payload = json_encode(['token' => $challenge['token'], 'credential' => $this->authenticator->create($challenge['options'])]);

        $this->ceremonies->register($user, GuardEnum::STANDARD->value, $payload, 'Laptop');
        $this->assertSame(1, $user->passkeys()->count());

        return $user;
    }
}
