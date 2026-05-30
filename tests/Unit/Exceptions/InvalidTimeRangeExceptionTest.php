<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\InvalidTimeRangeException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InvalidTimeRangeExceptionTest extends TestCase
{
    public function testItExtendsInvalidArgumentException(): void
    {
        $exception = new InvalidTimeRangeException(5, 2);

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    public function testConstructorSetsStartAndEndProperties(): void
    {
        $exception = new InvalidTimeRangeException(10, 3);

        $this->assertSame(10, $exception->start);
        $this->assertSame(3, $exception->end);
    }

    public function testMessageContainsStartAndEnd(): void
    {
        $exception = new InvalidTimeRangeException(7, 2);

        $this->assertStringContainsString('7', $exception->getMessage());
        $this->assertStringContainsString('2', $exception->getMessage());
        $this->assertSame('Invalid time range: start=7, end=2', $exception->getMessage());
    }

    public function testContextReturnsStartAndEnd(): void
    {
        $exception = new InvalidTimeRangeException(10, 3);

        $context = $exception->context();

        $this->assertSame(10, $context['start']);
        $this->assertSame(3, $context['end']);
    }

    public function testContextCalculatesDuration(): void
    {
        $exception = new InvalidTimeRangeException(10, 3);

        $this->assertSame(-7, $exception->context()['duration']);
    }

    public function testContextIncludesExceptionSelf(): void
    {
        $exception = new InvalidTimeRangeException(5, 1);

        $this->assertSame($exception, $exception->context()['exception']);
    }

    public function testContextDurationIsEndMinusStart(): void
    {
        $exception = new InvalidTimeRangeException(0, 0);

        $this->assertSame(0, $exception->context()['duration']);
    }
}
