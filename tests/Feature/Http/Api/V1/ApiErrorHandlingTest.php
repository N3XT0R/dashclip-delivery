<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class ApiErrorHandlingTest extends DatabaseTestCase
{
    public function testUnauthenticatedApiRequestReturnsJson401(): void
    {
        $response = $this->getJson('/api/user');
        $response->assertUnauthorized()->assertJsonStructure(['message']);
    }

    public function testMissingScopeReturnsJson403(): void
    {
        Route::middleware(['auth:api', 'scope:videos:read'])
            ->get('/api/v1/_scope-probe', static fn () => response()->json(['ok' => true]));
        $user = User::factory()->standard()->create();
        Passport::actingAs($user, ['channels:read']);

        $this->getJson('/api/v1/_scope-probe')->assertForbidden();
    }

    public function testPaginationConfigDefaults(): void
    {
        $this->assertSame(25, config('api.pagination.default_size'));
        $this->assertSame(100, config('api.pagination.max_size'));
    }
}
