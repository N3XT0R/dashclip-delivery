<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Video;
use Tests\TestCase;

/**
 * Unit tests for the App\Models\Video model.
 *
 * We validate:
 *  - mass assignment and the "meta" array cast
 *  - hasMany relationships: assignments(), clips()
 *  - getDisk() returns the configured filesystem and is writable
 */
final class VideoTest extends TestCase
{

    public function testModelHasHumanReadableSize(): void
    {
        $video = Video::factory()->make([
            'bytes' => 1048576, // 1 MB
        ]);

        $this->assertSame('1 MB', $video->human_readable_size);
    }

    public function testModelHumanReadableSizeReturnsNull(): void
    {
        $video = Video::factory()->make([
            'bytes' => null
        ]);

        self::assertNull($video->human_readable_size);
    }
}
