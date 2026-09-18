<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\LaravelPasskeys\Models\Passkey as BasePasskey;
use Spatie\LaravelPasskeys\Support\CredentialRecordConverter;
use Spatie\LaravelPasskeys\Support\Serializer;
use Webauthn\PublicKeyCredentialSource;

class Passkey extends BasePasskey
{
    /** Index binary credential identifiers consistently on every database driver. */
    public static function encodeCredentialId(string $raw): string
    {
        return hash('sha256', $raw);
    }

    /**
     * Store the credential identifier with the same encoding used to look it up during sign-in.
     *
     * The package setter binds to its own encoder, so lookups by the hashed identifier never matched.
     */
    public function data(): Attribute
    {
        $serializer = Serializer::make();

        return new Attribute(
            get: fn (string $value): PublicKeyCredentialSource => CredentialRecordConverter::toPublicKeyCredentialSource(
                $serializer->fromJson($value, PublicKeyCredentialSource::class)
            ),
            set: fn (PublicKeyCredentialSource $value): array => [
                'credential_id' => static::encodeCredentialId($value->publicKeyCredentialId),
                'data' => $serializer->toJson($value),
            ],
        );
    }
}
