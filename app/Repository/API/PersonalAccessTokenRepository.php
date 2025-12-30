<?php

declare(strict_types=1);

namespace App\Repository\API;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Passport\Token;

class PersonalAccessTokenRepository
{
    public function getValidTokensByUser(User $user): Collection
    {
        return $user->tokens()
            ->with('client')
            ->where('revoked', false)
            ->where('expires_at', '>', Date::now())
            ->get()
            ->filter(fn(Token $token) => $token->client->hasGrantType('personal_access'));
    }

    public function createToken(User $user, string $name, array $scopes = []): Token
    {
        $tokenResult = $user->createToken($name, $scopes);
        return $tokenResult->token;
    }

}
