<?php

declare(strict_types=1);

namespace Tests\Support;

use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use OpenSSLAsymmetricKey;
use RuntimeException;
use stdClass;

/**
 * In-memory ES256 authenticator that produces the same payloads the browser sends for passkey ceremonies.
 *
 * Behaves like a synced passkey provider: backup eligible, backed up, and a signature counter of zero.
 */
final class SoftwareAuthenticator
{
    private const FLAG_USER_PRESENT = 0x01;
    private const FLAG_USER_VERIFIED = 0x04;
    private const FLAG_BACKUP_ELIGIBLE = 0x08;
    private const FLAG_BACKED_UP = 0x10;
    private const FLAG_ATTESTED_CREDENTIAL = 0x40;

    private OpenSSLAsymmetricKey $key;

    private string $credentialId;

    private ?string $userHandle = null;

    public function __construct(
        private readonly string $rpId,
        private readonly string $origin,
    ) {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($key === false) {
            throw new RuntimeException('Unable to create an EC key pair.');
        }

        $this->key = $key;
        $this->credentialId = random_bytes(32);
    }

    /**
     * Answer navigator.credentials.create() for the given creation options.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function create(array $options): array
    {
        $this->userHandle = self::decode($options['user']['id']);

        $details = openssl_pkey_get_details($this->key)['ec'];
        $publicKey = MapObject::create()
            ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2))
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7))
            ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1))
            ->add(NegativeIntegerObject::create(-2), ByteStringObject::create(str_pad($details['x'], 32, "\0", STR_PAD_LEFT)))
            ->add(NegativeIntegerObject::create(-3), ByteStringObject::create(str_pad($details['y'], 32, "\0", STR_PAD_LEFT)));

        $authenticatorData = $this->authenticatorData(self::FLAG_ATTESTED_CREDENTIAL)
            .str_repeat("\0", 16)
            .pack('n', strlen($this->credentialId))
            .$this->credentialId
            .(string) $publicKey;

        $attestationObject = MapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
            ->add(TextStringObject::create('attStmt'), MapObject::create())
            ->add(TextStringObject::create('authData'), ByteStringObject::create($authenticatorData));

        return $this->credential([
            'clientDataJSON' => self::encode($this->clientData('webauthn.create', $options['challenge'])),
            'attestationObject' => self::encode((string) $attestationObject),
            'transports' => ['internal', 'hybrid'],
        ]);
    }

    /**
     * Answer navigator.credentials.get() for the given request options.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function get(array $options): array
    {
        $clientData = $this->clientData('webauthn.get', $options['challenge']);
        $authenticatorData = $this->authenticatorData(0);
        openssl_sign($authenticatorData.hash('sha256', $clientData, true), $signature, $this->key, OPENSSL_ALGO_SHA256);

        return $this->credential([
            'clientDataJSON' => self::encode($clientData),
            'authenticatorData' => self::encode($authenticatorData),
            'signature' => self::encode($signature),
            'userHandle' => $this->userHandle === null ? null : self::encode($this->userHandle),
        ]);
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    private function credential(array $response): array
    {
        return [
            'id' => self::encode($this->credentialId),
            'rawId' => self::encode($this->credentialId),
            'type' => 'public-key',
            'response' => $response,
            'clientExtensionResults' => new stdClass(),
        ];
    }

    private function authenticatorData(int $extraFlags): string
    {
        $flags = self::FLAG_USER_PRESENT | self::FLAG_USER_VERIFIED | self::FLAG_BACKUP_ELIGIBLE | self::FLAG_BACKED_UP | $extraFlags;

        return hash('sha256', $this->rpId, true).chr($flags).pack('N', 0);
    }

    private function clientData(string $type, string $challenge): string
    {
        return json_encode([
            'type' => $type,
            'challenge' => $challenge,
            'origin' => $this->origin,
            'crossOrigin' => false,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private static function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function decode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
