<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api;

use App\Models\User;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class AuthenticatedUserEndpointTest extends DatabaseTestCase
{
    public function testValidBearerTokenReturnsAuthenticatedUser(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $user->getKey()]);
    }

    public function testMissingBearerTokenReturnsUnauthorized(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }

    public function testInvalidBearerTokenReturnsUnauthorized(): void
    {
        $response = $this->getJson('/api/user', [
            'Authorization' => 'Bearer this-token-does-not-exist',
        ]);

        $response->assertUnauthorized();
    }
}
