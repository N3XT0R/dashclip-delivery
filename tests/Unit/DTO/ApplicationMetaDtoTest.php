<?php

declare(strict_types=1);

namespace Tests\Unit\DTO;

use App\DTO\Channel\ApplicationMetaDto;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class ApplicationMetaDtoTest extends TestCase
{
    public function testFromMetaArrayMapsAllFieldsCorrectly(): void
    {
        $data = [
            'new_channel' => ['name' => 'Test Channel'],
            'tos_accepted' => true,
            'tos_accepted_at' => '2025-01-01 12:00:00',
            'reject_reason' => 'Invalid data',
        ];

        $dto = ApplicationMetaDto::fromMetaArray($data);

        self::assertSame(['name' => 'Test Channel'], $dto->channel);
        self::assertTrue($dto->tosAccepted);
        self::assertInstanceOf(Carbon::class, $dto->tosAcceptedAt);
        self::assertSame('2025-01-01 12:00:00', $dto->tosAcceptedAt->toDateTimeString());
        self::assertSame('Invalid data', $dto->rejectReason);
    }

    public function testFromMetaArrayUsesDefaultsWhenKeysAreMissing(): void
    {
        $dto = ApplicationMetaDto::fromMetaArray([]);

        self::assertSame([], $dto->channel);
        self::assertFalse($dto->tosAccepted);
        self::assertNull($dto->tosAcceptedAt);
        self::assertNull($dto->rejectReason);
    }

    public function testToArraySerializesCorrectly(): void
    {
        $date = Carbon::create(2025, 1, 1, 12, 0, 0);

        $dto = new ApplicationMetaDto(
            channel: ['slug' => 'demo'],
            tosAccepted: true,
            tosAcceptedAt: $date,
            rejectReason: 'Rejected',
        );

        $array = $dto->toArray();

        self::assertSame(
            [
                'new_channel' => ['slug' => 'demo'],
                'tos_accepted' => true,
                'tos_accepted_at' => '2025-01-01 12:00:00',
                'reject_reason' => 'Rejected',
            ],
            $array
        );
    }

    public function testToArrayReturnsNullForTosAcceptedAtWhenNotSet(): void
    {
        $dto = new ApplicationMetaDto();

        $array = $dto->toArray();

        self::assertNull($array['tos_accepted_at']);
    }
}
