<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Re-index stored passkeys by the hashed credential identifier used during sign-in.
     *
     * Earlier registrations stored the raw identifier, so their passkeys could never be found again.
     */
    public function up(): void
    {
        DB::table('passkeys')->select(['id', 'data'])->orderBy('id')->each(static function (object $passkey): void {
            $credentialId = json_decode($passkey->data, true)['publicKeyCredentialId'] ?? null;
            if (! is_string($credentialId)) {
                return;
            }

            $base64 = strtr($credentialId, '-_', '+/');
            $raw = base64_decode(str_pad($base64, (int) ceil(strlen($base64) / 4) * 4, '='), true);
            if ($raw === false) {
                return;
            }

            DB::table('passkeys')->where('id', $passkey->id)->update(['credential_id' => hash('sha256', $raw)]);
        });
    }

    /**
     * The raw identifiers were unusable, so there is nothing meaningful to restore.
     */
    public function down(): void
    {
    }
};
