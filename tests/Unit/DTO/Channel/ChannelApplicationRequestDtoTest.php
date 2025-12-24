<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Channel;

use App\DTO\Channel\ChannelApplicationRequestDto;
use PHPUnit\Framework\TestCase;

final class ChannelApplicationRequestDtoTest extends TestCase
{
    public function testConstructorAssignsAllProperties(): void
    {
        $dto = new ChannelApplicationRequestDto(
            channelId: 123,
            note: 'Test note',
            otherChannelRequest: true,
            newChannelName: 'New Channel',
            newChannelCreatorName: 'Creator',
            newChannelEmail: 'creator@example.com',
            newChannelYoutubeName: 'YouTubeName',
        );

        self::assertSame(123, $dto->channelId);
        self::assertSame('Test note', $dto->note);
        self::assertTrue($dto->otherChannelRequest);
        self::assertSame('New Channel', $dto->newChannelName);
        self::assertSame('Creator', $dto->newChannelCreatorName);
        self::assertSame('creator@example.com', $dto->newChannelEmail);
        self::assertSame('YouTubeName', $dto->newChannelYoutubeName);
    }

    public function testConstructorAcceptsNullables(): void
    {
        $dto = new ChannelApplicationRequestDto(
            channelId: null,
            note: 'Only required field'
        );

        self::assertNull($dto->channelId);
        self::assertSame('Only required field', $dto->note);
        self::assertFalse($dto->otherChannelRequest);
        self::assertNull($dto->newChannelName);
        self::assertNull($dto->newChannelCreatorName);
        self::assertNull($dto->newChannelEmail);
        self::assertNull($dto->newChannelYoutubeName);
    }

    public function testDefaultOtherChannelRequestIsFalse(): void
    {
        $dto = new ChannelApplicationRequestDto(
            channelId: 1,
            note: 'Default test'
        );

        self::assertFalse($dto->otherChannelRequest);
    }
}
