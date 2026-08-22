<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\User;
use App\Models\Video;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class OfferEndpointsTest extends DatabaseTestCase
{
    private function actingOperator(array $scopes): array
    {
        $user = User::factory()->withOwnTeam()->standard()
            ->withRole(RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD->value)->create();
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user->getKey(), ['is_user_verified' => true]);
        Passport::actingAs($user, $scopes);

        return [$user, $channel];
    }

    public function testIndexShowsOffersOfOwnChannelOnly(): void
    {
        [, $channel] = $this->actingOperator(['offers:read']);
        $own = Assignment::factory()->create(['channel_id' => $channel->getKey()]);
        Assignment::factory()->create();

        $this->getJson('/api/v1/offers')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->getKey());
    }

    public function testOfferResponseNeverContainsDownloadToken(): void
    {
        [, $channel] = $this->actingOperator(['offers:read']);
        $offer = Assignment::factory()->create(['channel_id' => $channel->getKey()]);

        $json = $this->getJson('/api/v1/offers/' . $offer->getKey())->assertOk()->json('data');
        $this->assertArrayNotHasKey('download_token', $json);
    }

    public function testStoreCreatesOfferWithApiBatchAndExpiry(): void
    {
        [$user, $channel] = $this->actingOperator(['offers:write']);
        $video = Video::factory()->withClips(1, $user)->create();

        $response = $this->postJson('/api/v1/offers', [
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('assignments', [
            'id' => $response->json('data.id'),
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
            'status' => 'queued',
        ]);
        $this->assertDatabaseMissing('assignments', [
            'id' => $response->json('data.id'),
            'expires_at' => null,
        ]);
        // Necessary test setup: batch_id is server-generated and not exposed in the response
        // payload, so it must be fetched to link the assignment to its actual batch below.
        $batchId = Assignment::query()->findOrFail($response->json('data.id'))->batch_id;
        $this->assertDatabaseHas('batches', ['id' => $batchId, 'type' => 'api']);
    }

    public function testStoreRejectsInvisibleVideoOrChannel(): void
    {
        $this->actingOperator(['offers:write']);
        $foreignVideo = Video::factory()->create();
        $foreignChannel = Channel::factory()->create();

        $this->postJson('/api/v1/offers', [
            'video_id' => $foreignVideo->getKey(),
            'channel_id' => $foreignChannel->getKey(),
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['video_id', 'channel_id']);
    }

    public function testCommentSetsNote(): void
    {
        [, $channel] = $this->actingOperator(['offers:write']);
        $offer = Assignment::factory()->create(['channel_id' => $channel->getKey()]);

        $this->postJson('/api/v1/offers/' . $offer->getKey() . '/comment', ['note' => 'great clip'])
            ->assertOk()->assertJsonPath('data.note', 'great clip');
    }

    public function testStatusIsNotWritableViaStore(): void
    {
        [$user, $channel] = $this->actingOperator(['offers:write']);
        $video = Video::factory()->withClips(1, $user)->create();

        $response = $this->postJson('/api/v1/offers', [
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
            'status' => 'picked_up',
        ]);

        $response->assertUnprocessable();
    }

    public function testShowWithNonNumericIdReturns404(): void
    {
        $this->actingOperator(['offers:read']);

        $this->getJson('/api/v1/offers/abc')->assertNotFound();
    }

    public function testStoreOnExistingPairReturnsValidationErrorAndDoesNotOrphanBatch(): void
    {
        [$user, $channel] = $this->actingOperator(['offers:write']);
        $video = Video::factory()->withClips(1, $user)->create();

        $this->postJson('/api/v1/offers', [
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
        ])->assertCreated();

        $batchCountBefore = Batch::query()->count();

        $this->postJson('/api/v1/offers', [
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['video_id']);

        $this->assertSame($batchCountBefore, Batch::query()->count());
        $this->assertSame(1, Assignment::query()->count());
    }

    public function testIndexWithoutChannelOperatorRoleReturns403(): void
    {
        $user = User::factory()->withOwnTeam()->standard()->create();
        Passport::actingAs($user, ['offers:read']);

        $this->getJson('/api/v1/offers')->assertForbidden();
    }

    public function testShowForeignOfferReturns404(): void
    {
        $this->actingOperator(['offers:read']);
        $foreign = Assignment::factory()->create();

        $this->getJson('/api/v1/offers/' . $foreign->getKey())->assertNotFound();
    }

    public function testCommentOnForeignOfferReturns404AndLeavesNoteUnchanged(): void
    {
        $this->actingOperator(['offers:write']);
        $foreign = Assignment::factory()->create(['note' => 'original note']);

        $this->postJson('/api/v1/offers/' . $foreign->getKey() . '/comment', ['note' => 'hijacked'])
            ->assertNotFound();

        $this->assertSame('original note', $foreign->refresh()->note);
    }
}
