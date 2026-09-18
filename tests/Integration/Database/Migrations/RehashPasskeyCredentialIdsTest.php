<?php

declare(strict_types=1);

namespace Tests\Integration\Database\Migrations;

use App\Enum\Guard\GuardEnum;
use App\Models\Passkey;
use App\Models\User;
use App\Services\Passkeys\CeremonyService;
use Illuminate\Support\Facades\DB;
use Tests\DatabaseTestCase;
use Tests\Support\SoftwareAuthenticator;

final class RehashPasskeyCredentialIdsTest extends DatabaseTestCase
{
    public function testPasskeysStoredWithRawIdentifierCanSignInAfterMigration(): void
    {
        $this->startSession();
        $ceremonies = $this->app->make(CeremonyService::class);
        $rpId = (string) config('passkeys.relying_party.id');
        $authenticator = new SoftwareAuthenticator($rpId, 'https://'.$rpId);

        $user = User::factory()->standard()->create();
        $challenge = $ceremonies->begin($user, GuardEnum::STANDARD->value, 'register');
        $ceremonies->register($user, GuardEnum::STANDARD->value, json_encode([
            'token' => $challenge['token'],
            'credential' => $authenticator->create($challenge['options']),
        ]), 'Laptop');

        $passkey = Passkey::query()->sole();
        $expected = $passkey->credential_id;
        DB::table('passkeys')->where('id', $passkey->getKey())
            ->update(['credential_id' => mb_convert_encoding($passkey->data->publicKeyCredentialId, 'UTF-8')]);

        (require database_path('migrations/2026_09_18_180000_rehash_passkey_credential_ids.php'))->up();

        $this->assertSame($expected, DB::table('passkeys')->where('id', $passkey->getKey())->value('credential_id'));

        $challenge = $ceremonies->begin(null, GuardEnum::STANDARD->value, 'login');
        $signedIn = $ceremonies->authenticate(null, GuardEnum::STANDARD->value, json_encode([
            'token' => $challenge['token'],
            'credential' => $authenticator->get($challenge['options']),
        ]));

        $this->assertTrue($signedIn->is($user));
    }
}
