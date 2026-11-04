<?php

declare(strict_types=1);

namespace App\DTO\Dropbox;

/**
 * The credentials the storage connection signs in with.
 */
final readonly class DropboxCredentials
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
        public ?string $refreshToken = null,
    ) {
    }

    public function withRefreshToken(?string $refreshToken): self
    {
        return new self($this->clientId, $this->clientSecret, $refreshToken);
    }
}
