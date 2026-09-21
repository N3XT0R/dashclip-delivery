<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class ChannelEndpointsTest extends DatabaseTestCase
{
    private function actingUserWithChannel(array $scopes): array
    {
        $user = User::factory()->withOwnTeam()->standard()->create();
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user->getKey(), ['is_user_verified' => true]);
        Passport::actingAs($user, $scopes);

        return [$user, $channel];
    }

    public function testIndexListsOnlyAccessibleChannels(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:read']);
        Channel::factory()->create();

        $this->getJson('/api/v1/channels')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $channel->getKey());
    }

    public function testShowHidesAdminFields(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:read']);

        $json = $this->getJson('/api/v1/channels/' . $channel->getKey())
            ->assertOk()->json('data');
        $this->assertArrayNotHasKey('weight', $json);
        $this->assertArrayNotHasKey('weekly_quota', $json);
        $this->assertArrayNotHasKey('approved_at', $json);
    }

    public function testUpdateTogglesVideoReceptionPause(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), [
            'is_video_reception_paused' => true,
        ])->assertOk()->assertJsonPath('data.is_video_reception_paused', true);
    }

    public function testUpdateRejectsAdminFields(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), ['weight' => 99])
            ->assertUnprocessable();
        $this->assertDatabaseMissing('channels', ['id' => $channel->getKey(), 'weight' => 99]);
    }

    public function testForeignChannelReturns404(): void
    {
        $this->actingUserWithChannel(['channels:read', 'channels:write']);
        $foreign = Channel::factory()->create();

        $this->getJson('/api/v1/channels/' . $foreign->getKey())->assertNotFound();
    }

    public function testShowWithNonNumericIdReturns404(): void
    {
        $this->actingUserWithChannel(['channels:read']);

        $this->getJson('/api/v1/channels/abc')->assertNotFound();
    }

    public function testShowIncludesHomepageFlagAndLogoUrl(): void
    {
        Storage::fake('public');
        [, $channel] = $this->actingUserWithChannel(['channels:read']);
        $channel->update(['show_on_homepage' => false, 'logo_path' => 'channel-logos/logo.png']);

        $this->getJson('/api/v1/channels/' . $channel->getKey())
            ->assertOk()
            ->assertJsonPath('data.show_on_homepage', false)
            ->assertJsonPath('data.logo_url', Storage::disk('public')->url('channel-logos/logo.png'));
    }

    public function testUpdateChangesEveryChannelSetting(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), [
            'name' => 'Renamed Channel',
            'creator_name' => 'Jane Creator',
            'email' => 'renamed@example.com',
            'youtube_name' => 'renamedchannel',
            'is_video_reception_paused' => true,
            'show_on_homepage' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed Channel')
            ->assertJsonPath('data.creator_name', 'Jane Creator')
            ->assertJsonPath('data.email', 'renamed@example.com')
            ->assertJsonPath('data.youtube_name', 'renamedchannel')
            ->assertJsonPath('data.is_video_reception_paused', true)
            ->assertJsonPath('data.show_on_homepage', false);

        $this->assertDatabaseHas('channels', [
            'id' => $channel->getKey(),
            'name' => 'Renamed Channel',
            'email' => 'renamed@example.com',
            'show_on_homepage' => false,
        ]);
    }

    public function testUpdateKeepsFieldsThatAreNotSent(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);
        $email = $channel->email;

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), ['name' => 'Only Name Changed'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Only Name Changed')
            ->assertJsonPath('data.email', $email);
    }

    public function testUpdateAllowsClearingOptionalFields(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);
        $channel->update(['creator_name' => 'Someone', 'youtube_name' => 'someone']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), [
            'creator_name' => null,
            'youtube_name' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.creator_name', null)
            ->assertJsonPath('data.youtube_name', null);
    }

    public function testUpdateRejectsAnEmptyBody(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), [])->assertUnprocessable();
    }

    public function testUpdateRejectsClearingRequiredFields(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), ['name' => '', 'email' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function testUpdateRejectsAnInvalidEmail(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function testUpdateRejectsNameAndEmailOfAnotherChannel(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);
        $other = Channel::factory()->create();

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), [
            'name' => $other->name,
            'email' => $other->email,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function testUpdateAcceptsTheChannelsOwnNameAndEmail(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), [
            'name' => $channel->name,
            'email' => $channel->email,
        ])->assertOk();
    }

    public function testUpdateRejectsTheLogoPath(): void
    {
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->patchJson('/api/v1/channels/' . $channel->getKey(), ['logo_path' => 'channel-logos/x.png'])
            ->assertUnprocessable();
        $this->assertDatabaseMissing('channels', ['id' => $channel->getKey(), 'logo_path' => 'channel-logos/x.png']);
    }

    public function testUploadLogoStoresAShrunkImage(): void
    {
        Storage::fake('public');
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $response = $this->post('/api/v1/channels/' . $channel->getKey() . '/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 800, 400),
        ], ['Accept' => 'application/json'])->assertOk();

        $path = $channel->refresh()->logo_path;
        self::assertNotNull($path);
        self::assertStringStartsWith('channel-logos/', $path);
        Storage::disk('public')->assertExists($path);
        $response->assertJsonPath('data.logo_url', Storage::disk('public')->url($path));

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
        self::assertSame([256, 128], [$width, $height]);
    }

    public function testUploadLogoReplacesThePreviousFile(): void
    {
        Storage::fake('public');
        [, $channel] = $this->actingUserWithChannel(['channels:write']);
        Storage::disk('public')->put('channel-logos/old.png', 'old');
        $channel->update(['logo_path' => 'channel-logos/old.png']);

        $this->post('/api/v1/channels/' . $channel->getKey() . '/logo', [
            'logo' => UploadedFile::fake()->image('logo.jpg', 100, 100),
        ], ['Accept' => 'application/json'])->assertOk();

        Storage::disk('public')->assertMissing('channel-logos/old.png');
        Storage::disk('public')->assertExists($channel->refresh()->logo_path);
    }

    public function testUploadLogoRejectsOtherFileTypes(): void
    {
        Storage::fake('public');
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->post('/api/v1/channels/' . $channel->getKey() . '/logo', [
            'logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['logo']);
    }

    public function testUploadLogoRejectsFilesLargerThan512Kilobytes(): void
    {
        Storage::fake('public');
        [, $channel] = $this->actingUserWithChannel(['channels:write']);

        $this->post('/api/v1/channels/' . $channel->getKey() . '/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 100, 100)->size(600),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['logo']);
    }

    public function testUploadLogoRequiresTheWriteScope(): void
    {
        Storage::fake('public');
        [, $channel] = $this->actingUserWithChannel(['channels:read']);

        $this->post('/api/v1/channels/' . $channel->getKey() . '/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 100, 100),
        ], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function testDeleteLogoRemovesTheFile(): void
    {
        Storage::fake('public');
        [, $channel] = $this->actingUserWithChannel(['channels:write']);
        Storage::disk('public')->put('channel-logos/current.png', 'logo');
        $channel->update(['logo_path' => 'channel-logos/current.png']);

        $this->deleteJson('/api/v1/channels/' . $channel->getKey() . '/logo')
            ->assertOk()
            ->assertJsonPath('data.logo_url', null);

        Storage::disk('public')->assertMissing('channel-logos/current.png');
        self::assertNull($channel->refresh()->logo_path);
    }

    public function testLogoEndpointsReturn404ForForeignChannels(): void
    {
        Storage::fake('public');
        $this->actingUserWithChannel(['channels:write']);
        $foreign = Channel::factory()->create();

        $this->post('/api/v1/channels/' . $foreign->getKey() . '/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 100, 100),
        ], ['Accept' => 'application/json'])->assertNotFound();
        $this->deleteJson('/api/v1/channels/' . $foreign->getKey() . '/logo')->assertNotFound();
    }
}
