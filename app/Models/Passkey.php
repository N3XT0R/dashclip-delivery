<?php

namespace App\Models;

use Spatie\LaravelPasskeys\Models\Passkey as BasePasskey;

class Passkey extends BasePasskey
{
    /** Index binary credential identifiers consistently on every database driver. */
    public static function encodeCredentialId(string $raw): string
    {
        return hash('sha256', $raw);
    }
}
