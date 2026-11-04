<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Offer;

use App\Application\Offer\CreateOfferUseCase;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Video;
use Illuminate\Validation\ValidationException;
use Tests\DatabaseTestCase;

final class CreateOfferUseCaseTest extends DatabaseTestCase
{
    public function testAVideoDeletedSinceTheRequestWasCheckedGetsNoOfferAndNoBatch(): void
    {
        $video = Video::factory()->create();
        $channel = Channel::factory()->create();
        $batches = Batch::query()->count();
        Video::query()->whereKey($video->getKey())->first()->delete();

        try {
            app(CreateOfferUseCase::class)->handle($video, $channel);
            self::fail('Expected the offer to be rejected.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('video_id', $exception->errors());
        }

        $this->assertDatabaseMissing('assignments', ['video_id' => $video->getKey()]);
        self::assertSame($batches, Batch::query()->count());
    }
}
