<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;

final class OfferDownloadTest extends DatabaseTestCase
{
    private ?string $videoPath = null;

    protected function tearDown(): void
    {
        if ($this->videoPath !== null) {
            Storage::disk('local')->delete($this->videoPath);
        }

        parent::tearDown();
    }

    /** @return array{User, Channel} */
    private function actingOperator(array $scopes = ['offers:download']): array
    {
        $user = User::factory()->withOwnTeam()->standard()
            ->withRole(RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD->value)->create();
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user->getKey(), ['is_user_verified' => true]);
        Passport::actingAs($user, $scopes);

        return [$user, $channel];
    }

    private function offerWithFile(Channel $channel, string $status = 'notified'): Assignment
    {
        $this->videoPath = 'testing/offer-download-' . bin2hex(random_bytes(8)) . '.mp4';
        Storage::disk('local')->put($this->videoPath, 'offered video contents');
        $video = Video::factory()->create(['disk' => 'local', 'path' => $this->videoPath, 'bytes' => 22]);

        return Assignment::factory()->forChannel($channel)->forVideo($video)->create(['status' => $status]);
    }

    /** @return array<string, array{string}> */
    public static function downloadableStatuses(): array
    {
        return ['queued' => ['queued'], 'notified' => ['notified'], 'picked up' => ['picked_up']];
    }

    #[DataProvider('downloadableStatuses')]
    public function testDownloadsTheFileAndTracksPickup(string $status): void
    {
        [, $channel] = $this->actingOperator();
        $offer = $this->offerWithFile($channel, $status);
        $response = $this->withHeader('User-Agent', 'Operator integration')
            ->get('/api/v1/offers/' . $offer->getKey() . '/download');

        $response->assertOk()->assertDownload(basename($this->videoPath));
        $this->assertSame('offered video contents', $response->streamedContent());
        $this->assertDatabaseHas('assignments', ['id' => $offer->getKey(), 'status' => 'picked_up']);
        $this->assertDatabaseHas('downloads', [
            'assignment_id' => $offer->getKey(), 'user_agent' => 'Operator integration', 'bytes_sent' => 22,
        ]);
    }

    public function testRequiresAuthentication(): void
    {
        $this->getJson('/api/v1/offers/1/download')->assertUnauthorized();
    }

    public function testReadScopeDoesNotPermitDownloads(): void
    {
        [, $channel] = $this->actingOperator(['offers:read']);
        $offer = $this->offerWithFile($channel);
        $this->getJson('/api/v1/offers/' . $offer->getKey() . '/download')->assertForbidden();
        $this->assertDatabaseCount('downloads', 0);
    }

    public function testRequiresOfferPermissionEvenWithChannelAccess(): void
    {
        [$user, $channel] = $this->actingOperator();
        $user->syncRoles([]);
        $offer = $this->offerWithFile($channel);
        $this->getJson('/api/v1/offers/' . $offer->getKey() . '/download')->assertForbidden();
        $this->assertDatabaseCount('downloads', 0);
    }

    public function testForeignAndMissingOffersAreNotFound(): void
    {
        $this->actingOperator();
        $offer = Assignment::factory()->create();
        $this->getJson('/api/v1/offers/' . $offer->getKey() . '/download')->assertNotFound();
        $this->getJson('/api/v1/offers/999999999/download')->assertNotFound();
        $this->assertDatabaseCount('downloads', 0);
    }

    public function testVisibilityThroughSubmittedClipsDoesNotPermitDownloads(): void
    {
        [$user] = $this->actingOperator(['offers:read', 'offers:download']);
        $video = Video::factory()->withClips(1, $user)->create();
        $offer = Assignment::factory()->forVideo($video)->create();

        $this->getJson('/api/v1/offers/' . $offer->getKey())->assertOk();
        $this->getJson('/api/v1/offers/' . $offer->getKey() . '/download')->assertNotFound();
        $this->assertDatabaseCount('downloads', 0);
    }

    /** @return array<string, array{string, bool}> */
    public static function unavailableOffers(): array
    {
        return [
            'expired timestamp' => ['notified', true],
            'expired status' => ['expired', false],
            'rejected status' => ['rejected', false],
        ];
    }

    #[DataProvider('unavailableOffers')]
    public function testUnavailableOffersAreGone(string $status, bool $expired): void
    {
        [, $channel] = $this->actingOperator();
        $offer = $this->offerWithFile($channel, $status);
        if ($expired) {
            $offer->update(['expires_at' => now()->subSecond()]);
        }

        $this->getJson('/api/v1/offers/' . $offer->getKey() . '/download')->assertGone();
        $this->assertDatabaseCount('downloads', 0);
        $this->assertDatabaseHas('assignments', ['id' => $offer->getKey(), 'status' => $status]);
    }

    public function testMissingFileIsNotTracked(): void
    {
        [, $channel] = $this->actingOperator();
        $offer = $this->offerWithFile($channel);
        Storage::disk('local')->delete($this->videoPath);

        $this->getJson('/api/v1/offers/' . $offer->getKey() . '/download')->assertNotFound();
        $this->assertDatabaseCount('downloads', 0);
        $this->assertDatabaseHas('assignments', ['id' => $offer->getKey(), 'status' => 'notified']);
    }
}
