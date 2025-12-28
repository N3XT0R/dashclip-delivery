<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\DTO\Channel\ApplicationMetaDto;
use App\Models\ChannelApplication;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class ChannelApplicationTest extends TestCase
{
    public function testIsNewChannelReturnsTrueWhenChannelIdIsNull(): void
    {
        $application = new ChannelApplication([
            'channel_id' => null,
        ]);

        self::assertTrue($application->isNewChannel());
    }

    public function testIsNewChannelReturnsFalseWhenChannelIdIsSet(): void
    {
        $application = new ChannelApplication([
            'channel_id' => 42,
        ]);

        self::assertFalse($application->isNewChannel());
    }

    public function testMetaAccessorReturnsApplicationMetaDtoFromArray(): void
    {
        $application = new ChannelApplication([
            'meta' => [
                'new_channel' => ['slug' => 'test'],
                'tos_accepted' => true,
                'tos_accepted_at' => '2025-01-01 12:00:00',
                'reject_reason' => 'Invalid',
            ],
        ]);

        $meta = $application->meta;

        self::assertInstanceOf(ApplicationMetaDto::class, $meta);
        self::assertSame(['slug' => 'test'], $meta->channel);
        self::assertTrue($meta->tosAccepted);
        self::assertInstanceOf(Carbon::class, $meta->tosAcceptedAt);
        self::assertSame(
            '2025-01-01 12:00:00',
            $meta->tosAcceptedAt->toDateTimeString()
        );
        self::assertSame('Invalid', $meta->rejectReason);
    }

    public function testMetaAccessorReturnsDefaultDtoWhenMetaIsNull(): void
    {
        $application = new ChannelApplication([
            'meta' => null,
        ]);

        $meta = $application->meta;

        self::assertInstanceOf(ApplicationMetaDto::class, $meta);
        self::assertSame([], $meta->channel);
        self::assertFalse($meta->tosAccepted);
        self::assertNull($meta->tosAcceptedAt);
        self::assertNull($meta->rejectReason);
    }

    public function testMetaAccessorReturnsDefaultDtoWhenMetaIsEmptyArray(): void
    {
        $application = new ChannelApplication([
            'meta' => [],
        ]);

        $meta = $application->meta;

        self::assertInstanceOf(ApplicationMetaDto::class, $meta);
        self::assertSame([], $meta->channel);
        self::assertFalse($meta->tosAccepted);
        self::assertNull($meta->tosAcceptedAt);
        self::assertNull($meta->rejectReason);
    }
}
