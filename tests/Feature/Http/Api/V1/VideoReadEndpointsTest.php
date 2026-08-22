<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Models\User;
use App\Models\Video;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class VideoReadEndpointsTest extends DatabaseTestCase
{
    private function actingUser(array $scopes = ['videos:read']): User
    {
        $user = User::factory()->withOwnTeam()->standard()->create();
        Passport::actingAs($user, $scopes);

        return $user;
    }

    public function testIndexListsOnlyOwnVideosWithPaginationMeta(): void
    {
        $user = $this->actingUser();
        $own = Video::factory()->withClips(1, $user)->create();
        Video::factory()->create();

        $response = $this->getJson('/api/v1/videos');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->getKey())
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonStructure([
                'data' => [['id', 'original_name', 'ext', 'bytes', 'processing_status', 'created_at']],
                'meta' => ['pagination' => ['current_page', 'per_page', 'total', 'total_pages']],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);
        $this->assertArrayNotHasKey('path', $response->json('data.0'));
        $this->assertArrayNotHasKey('hash', $response->json('data.0'));
    }

    public function testIndexSupportsFilterSortAndPageSize(): void
    {
        $user = $this->actingUser();
        Video::factory()->withClips(1, $user)->create(['original_name' => 'alpha.mp4']);
        Video::factory()->withClips(1, $user)->create(['original_name' => 'beta.mp4']);

        $this->getJson('/api/v1/videos?filter[original_name]=alpha')
            ->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/videos?sort=original_name&page[size]=1&page[number]=2')
            ->assertOk()
            ->assertJsonPath('data.0.original_name', 'beta.mp4')
            ->assertJsonPath('meta.pagination.per_page', 1);
    }

    public function testPageSizeIsCappedAtMax(): void
    {
        $this->actingUser();
        $this->getJson('/api/v1/videos?page[size]=9999')
            ->assertOk()->assertJsonPath('meta.pagination.per_page', 100);
    }

    public function testShowForeignVideoReturns404(): void
    {
        $this->actingUser();
        $foreign = Video::factory()->create();

        $this->getJson('/api/v1/videos/' . $foreign->getKey())->assertNotFound();
    }

    public function testMissingScopeReturns403(): void
    {
        $this->actingUser(['channels:read']);
        $this->getJson('/api/v1/videos')->assertForbidden();
    }

    public function testUnauthenticatedReturns401(): void
    {
        $this->getJson('/api/v1/videos')->assertUnauthorized();
    }
}
