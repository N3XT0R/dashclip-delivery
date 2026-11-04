<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Models\User;
use Filament\Facades\Filament;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class OAuthConsentPagesTest extends DatabaseTestCase
{
    private const string REDIRECT_URI = 'https://client.example.test/callback';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Passport::tokensCan(['videos:read' => 'Read your videos']);
        $this->user = User::factory()->standard()->create();
    }

    public function testGuestsAreSentToTheSignInInsteadOfAnError(): void
    {
        $login = Filament::getPanel(PanelEnum::STANDARD->value)->getLoginUrl();

        $this->get($this->authorizeUrl($this->codeClient()))->assertRedirect($login);
        $this->get('/oauth/device')->assertOk();
        $this->get('/oauth/device/authorize?user_code=ABCD-EFGH')->assertRedirect($login);
    }

    public function testConsentUsesTheSignInOfTheUserAreaNotTheAdministration(): void
    {
        $this->actingAs($this->user, GuardEnum::DEFAULT->value)
            ->get($this->authorizeUrl($this->codeClient()))
            ->assertRedirect(Filament::getPanel(PanelEnum::STANDARD->value)->getLoginUrl());
    }

    public function testConsentPageNamesTheApplicationAndTheRequestedPermissions(): void
    {
        $client = $this->codeClient();

        $this->actingAs($this->user, GuardEnum::STANDARD->value)
            ->get($this->authorizeUrl($client))
            ->assertOk()
            ->assertSee('Photo Sync')
            ->assertSee('Read your videos')
            ->assertSee(__('oauth.consent.approve'))
            ->assertSee(__('oauth.consent.deny'))
            ->assertSee('noindex, nofollow', false);
    }

    public function testApprovingSendsAnAuthorizationCodeToTheApplication(): void
    {
        $client = $this->codeClient();
        $this->actingAs($this->user, GuardEnum::STANDARD->value)->get($this->authorizeUrl($client))->assertOk();

        $response = $this->post('/oauth/authorize', [
            'state' => 'xyz',
            'client_id' => $client->getKey(),
            'auth_token' => session('authToken'),
        ]);

        $location = (string)$response->headers->get('Location');
        self::assertStringStartsWith(self::REDIRECT_URI . '?code=', $location);
        self::assertStringContainsString('state=xyz', $location);
    }

    public function testDenyingTellsTheApplicationTheAccessWasDenied(): void
    {
        $client = $this->codeClient();
        $this->actingAs($this->user, GuardEnum::STANDARD->value)->get($this->authorizeUrl($client))->assertOk();

        $response = $this->delete('/oauth/authorize', [
            'state' => 'xyz',
            'client_id' => $client->getKey(),
            'auth_token' => session('authToken'),
        ]);

        self::assertStringContainsString('error=access_denied', (string)$response->headers->get('Location'));
    }

    public function testDeviceCodePageAsksForTheCodeAndConfirmsTheOutcome(): void
    {
        $this->actingAs($this->user, GuardEnum::STANDARD->value)
            ->get('/oauth/device')
            ->assertOk()
            ->assertSee(__('oauth.device.code_label'))
            ->assertSee('name="user_code"', false);

        $this->withSession(['status' => 'authorization-approved'])
            ->get('/oauth/device')
            ->assertSee(__('oauth.device.approved'));
    }

    public function testDeviceConsentPageNamesTheApplicationForAValidCode(): void
    {
        $client = app(ClientRepository::class)->createDeviceAuthorizationGrantClient('TV App', confidential: false);
        $userCode = $this->post('/oauth/device/code', [
            'client_id' => $client->getKey(),
            'scope' => 'videos:read',
        ])->assertOk()->json('user_code');

        $this->actingAs($this->user, GuardEnum::STANDARD->value)
            ->get('/oauth/device/authorize?user_code=' . $userCode)
            ->assertOk()
            ->assertSee('TV App')
            ->assertSee('Read your videos')
            ->assertSee(__('oauth.device.consent_hint'));
    }

    public function testAnUnknownDeviceCodeIsReportedOnTheCodePage(): void
    {
        $this->actingAs($this->user, GuardEnum::STANDARD->value)
            ->followingRedirects()
            ->get('/oauth/device/authorize?user_code=WRONG-CODE')
            ->assertOk()
            ->assertSee(__('oauth.device.invalid_code'));
    }

    private function codeClient(): Client
    {
        return app(ClientRepository::class)->createAuthorizationCodeGrantClient('Photo Sync', [self::REDIRECT_URI]);
    }

    private function authorizeUrl(Client $client): string
    {
        return '/oauth/authorize?' . http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => self::REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'videos:read',
            'state' => 'xyz',
        ]);
    }
}
