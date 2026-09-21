<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Enum\Guard\GuardEnum;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use N3XT0R\LaravelPassportAuthorizationCore\Services\GrantService;
use Tests\DatabaseTestCase;

/**
 * Every documented grant end to end: obtain a real token at /oauth/token and call the API with it.
 */
final class OAuthTokenFlowsTest extends DatabaseTestCase
{
    private const string REDIRECT_URI = 'https://client.example.test/callback';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Passport::tokensCan(['videos:read' => 'Read your videos']);
        $this->user = User::factory()->withOwnTeam()->standard()->create();
    }

    public function testAuthorizationCodeGrantIssuesTokensThatReachTheApi(): void
    {
        $client = $this->codeClient();

        $code = $this->approveAndGetCode($client, []);
        $tokens = $this->token([
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'redirect_uri' => self::REDIRECT_URI,
            'code' => $code,
        ]);

        self::assertNotEmpty($tokens['refresh_token']);
        $this->callApi($tokens['access_token'])->assertOk();
    }

    public function testAuthorizationCodeGrantWithPkceWorksForPublicClients(): void
    {
        $client = $this->grantScopes(
            $this->clients()->createAuthorizationCodeGrantClient('Mobile App', [self::REDIRECT_URI], false)
        );
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $code = $this->approveAndGetCode($client, [
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]);
        $tokens = $this->token([
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'redirect_uri' => self::REDIRECT_URI,
            'code_verifier' => $verifier,
            'code' => $code,
        ]);

        $this->callApi($tokens['access_token'])->assertOk();
    }

    public function testRefreshTokenGrantIssuesANewWorkingAccessToken(): void
    {
        $client = $this->codeClient();
        $first = $this->token([
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'redirect_uri' => self::REDIRECT_URI,
            'code' => $this->approveAndGetCode($client, []),
        ]);

        $refreshed = $this->token([
            'grant_type' => 'refresh_token',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'refresh_token' => $first['refresh_token'],
        ]);

        self::assertNotSame($first['access_token'], $refreshed['access_token']);
        $this->callApi($refreshed['access_token'])->assertOk();
    }

    public function testDeviceCodeGrantIssuesATokenOnceTheUserApproved(): void
    {
        $client = $this->grantScopes($this->clients()->createDeviceAuthorizationGrantClient('TV App', false));
        $device = $this->post('/oauth/device/code', ['client_id' => $client->getKey(), 'scope' => 'videos:read'])
            ->assertOk()
            ->json();

        $this->token([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => $client->getKey(),
            'device_code' => $device['device_code'],
        ], expectedStatus: 400);

        $this->actingAs($this->user, GuardEnum::STANDARD->value)
            ->get('/oauth/device/authorize?user_code=' . $device['user_code'])
            ->assertOk();
        $this->post('/oauth/device/authorize', ['auth_token' => session('authToken')])
            ->assertRedirect(route('passport.device'));

        $tokens = $this->token([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => $client->getKey(),
            'device_code' => $device['device_code'],
        ]);

        $this->callApi($tokens['access_token'])->assertOk();
    }

    public function testClientCredentialsGrantIssuesATokenWithoutUserContext(): void
    {
        $client = $this->grantScopes($this->clients()->createClientCredentialsGrantClient('Backend Job'));

        $tokens = $this->token([
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'scope' => 'videos:read',
        ]);

        self::assertNotEmpty($tokens['access_token']);
        self::assertArrayNotHasKey('refresh_token', $tokens);
        // The resource endpoints act on behalf of a user, as the documentation of the grant states.
        $this->callApi($tokens['access_token'])->assertUnauthorized();
    }

    public function testPersonalAccessTokensReachTheApi(): void
    {
        $this->grantScopes($this->clients()->createPersonalAccessGrantClient('Personal Access Client', 'users'));

        $token = $this->user->createToken('CLI', ['videos:read'])->accessToken;

        $this->callApi($token)->assertOk();
    }

    public function testTokensOfAClientWithoutGrantedScopesAreRejectedByTheApi(): void
    {
        // Scopes are only kept when they were granted to the client, as creating it in the user area does.
        $this->clients()->createPersonalAccessGrantClient('Personal Access Client', 'users');

        $result = $this->user->createToken('CLI', ['videos:read']);

        self::assertSame([], $result->token->scopes);
        $this->callApi($result->accessToken)->assertForbidden();
    }

    private function codeClient(): Client
    {
        return $this->grantScopes(
            $this->clients()->createAuthorizationCodeGrantClient('Photo Sync', [self::REDIRECT_URI])
        );
    }

    private function clients(): ClientRepository
    {
        return app(ClientRepository::class);
    }

    /**
     * Grant the client its scopes the way creating a client in the user area does.
     */
    private function grantScopes(Client $client): Client
    {
        app(GrantService::class)->giveGrantsToTokenable($client, ['videos:read'], contextClient: $client);

        return $client;
    }

    /**
     * @param array<string, string> $extraQuery
     */
    private function approveAndGetCode(Client $client, array $extraQuery): string
    {
        $this->actingAs($this->user, GuardEnum::STANDARD->value)
            ->get('/oauth/authorize?' . http_build_query([
                'client_id' => $client->getKey(),
                'redirect_uri' => self::REDIRECT_URI,
                'response_type' => 'code',
                'scope' => 'videos:read',
                'state' => 'xyz',
                ...$extraQuery,
            ]))
            ->assertOk();

        $location = (string)$this->post('/oauth/authorize', [
            'state' => 'xyz',
            'client_id' => $client->getKey(),
            'auth_token' => session('authToken'),
        ])->headers->get('Location');

        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        self::assertArrayHasKey('code', $query, 'No authorization code in ' . $location);

        return (string)$query['code'];
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function token(array $body, int $expectedStatus = 200): array
    {
        // A token request never carries a session; start from a clean, signed-out client.
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        return $this->postJson('/oauth/token', $body)->assertStatus($expectedStatus)->json();
    }

    private function callApi(string $accessToken): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($accessToken)->getJson('/api/v1/videos');
    }
}
