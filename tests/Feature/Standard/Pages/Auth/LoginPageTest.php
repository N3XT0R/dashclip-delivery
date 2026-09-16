<?php

declare(strict_types=1);

namespace Tests\Feature\Standard\Pages\Auth;

use App\Enum\Guard\GuardEnum;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Filament\Facades\Filament;
use Illuminate\Testing\TestResponse;
use Tests\DatabaseTestCase;

final class LoginPageTest extends DatabaseTestCase
{
    public function testGuestCanSwitchTheLoginLanguage(): void
    {
        $this->withHeader('Accept-Language', 'de')->get('/standard/login')->assertOk()->assertSee('Sicher anmelden.')
            ->assertSee(route('filament.standard.auth.register'), false)
            ->assertSee(route('filament.standard.auth.password-reset.request'), false);

        $this->post(route('public.locale'), ['locale' => 'en', 'return_to' => '/standard/login'])
            ->assertRedirect('/standard/login')->assertCookie('public_locale', 'en');

        $this->withCookie('public_locale', 'en')->get('/standard/login')
            ->assertOk()->assertSee('Sign in securely.')->assertSee('Welcome back');
    }

    public function testTheRedesignedFormAuthenticatesThroughFilament(): void
    {
        $user = User::factory()->withOwnTeam()->standard(GuardEnum::STANDARD)->create();

        $response = $this->submitCredentials($user->email, 'password');

        $response->assertOk();
        $this->assertAuthenticatedAs($user, GuardEnum::STANDARD->value);
        $this->assertNotEmpty($response->json('components.0.effects.redirect'));
    }

    public function testInvalidCredentialsKeepTheUserOnTheLoginForm(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create();

        $response = $this->submitCredentials($user->email, 'incorrect-password');

        $response->assertOk();
        $this->assertGuest(GuardEnum::STANDARD->value);
        $snapshot = json_decode($response->json('components.0.snapshot'), true);
        $this->assertArrayHasKey('data.email', $snapshot['memo']['errors']);
    }

    public function testMultiFactorAccountsStillReceiveTheirChallenge(): void
    {
        $user = User::factory()->withOwnTeam()->standard(GuardEnum::STANDARD)->create();
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

        $response = $this->submitCredentials($user->email, 'password')->assertOk();

        $this->assertGuest(GuardEnum::STANDARD->value);
        $snapshot = json_decode($response->json('components.0.snapshot'), true);
        $this->assertNotEmpty($snapshot['data']['userUndertakingMultiFactorAuthentication']);
        $this->assertStringContainsString(__('filament-panels::auth/pages/login.multi_factor.heading'), $response->json('components.0.effects.html'));
    }

    public function testGuestCookieDoesNotOverrideAnAuthenticatedUsersLanguage(): void
    {
        $user = User::factory()->withOwnTeam()->standard(GuardEnum::STANDARD)->create(['locale' => 'de']);

        $this->actingAs($user, GuardEnum::STANDARD->value)->withCookie('public_locale', 'en')
            ->get('/standard/'.$user->teams()->firstOrFail()->slug)
            ->assertOk()->assertSee('Willkommen');
    }

    /** Submit the actual rendered Livewire snapshot through its HTTP endpoint. */
    private function submitCredentials(string $email, string $password): TestResponse
    {
        Filament::setCurrentPanel('standard');
        $response = $this->get('/standard/login')->assertOk();
        $document = new DOMDocument();
        @$document->loadHTML($response->getContent());
        $component = (new DOMXPath($document))->query('//*[@*[name()="wire:snapshot"]]')->item(0);
        $this->assertNotNull($component);

        return $this->postJson(route('default-livewire.update'), [
            'components' => [[
                'snapshot' => $component->getAttribute('wire:snapshot'),
                'updates' => ['data.email' => $email, 'data.password' => $password],
                'calls' => [['method' => 'authenticate', 'params' => []]],
            ]],
        ], ['X-Livewire' => 'true']);
    }
}
