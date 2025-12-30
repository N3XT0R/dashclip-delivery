<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repository\UserRepository;
use Laravel\Passport\Token;

class PersonalAccessTokenService
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function createToken(User $user, string $name, array $scopes = []): string
    {
    }

    public function revokeToken(User $user, Token $token): void
    {
    }

    public function isTokenValid(Token $token): bool
    {
    }

    public function refreshToken(Token $token): string
    {
    }
}
