<?php

declare(strict_types=1);

namespace Tests\Unit\Enum\Channel;

use App\Enum\Channel\ApplicationEnum;
use PHPUnit\Framework\TestCase;

final class ApplicationEnumTest extends TestCase
{
    public function testEnumValuesAreCorrect(): void
    {
        self::assertSame('pending', ApplicationEnum::PENDING->value);
        self::assertSame('approved', ApplicationEnum::APPROVED->value);
        self::assertSame('rejected', ApplicationEnum::REJECTED->value);
    }

    public function testNonRejectedReturnsPendingAndApproved(): void
    {
        self::assertSame(
            [
                ApplicationEnum::PENDING->value,
                ApplicationEnum::APPROVED->value,
            ],
            ApplicationEnum::nonRejected()
        );
    }

    public function testAllReturnsAllEnumValues(): void
    {
        self::assertSame(
            [
                ApplicationEnum::PENDING->value,
                ApplicationEnum::APPROVED->value,
                ApplicationEnum::REJECTED->value,
            ],
            ApplicationEnum::all()
        );
    }

    public function testNonRejectedDoesNotContainRejected(): void
    {
        self::assertNotContains(
            ApplicationEnum::REJECTED->value,
            ApplicationEnum::nonRejected()
        );
    }
}
