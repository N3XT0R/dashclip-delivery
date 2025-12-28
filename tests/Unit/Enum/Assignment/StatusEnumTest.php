<?php

declare(strict_types=1);

namespace Tests\Unit\Enum\Assignment;

use App\Enum\StatusEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StatusEnumTest extends TestCase
{
    public function testGetReadyStatusReturnsQueuedAndNotified(): void
    {
        $result = StatusEnum::getReadyStatus();

        $this->assertSame(
            [
                StatusEnum::QUEUED->value,
                StatusEnum::NOTIFIED->value,
            ],
            $result
        );
    }

    public function testGetRequeueStatusesReturnsExpiredAndRejected(): void
    {
        $result = StatusEnum::getRequeueStatuses();

        $this->assertSame(
            [
                StatusEnum::EXPIRED->value,
                StatusEnum::REJECTED->value,
            ],
            $result
        );
    }

    public function testGetReturnableStatusesReturnsPickedUpNotifiedAndQueued(): void
    {
        $result = StatusEnum::getReturnableStatuses();

        $this->assertSame(
            [
                StatusEnum::PICKEDUP->value,
                StatusEnum::NOTIFIED->value,
                StatusEnum::QUEUED->value,
            ],
            $result
        );
    }

    public function testGetEditableStatusesIsAliasForGetReturnableStatuses(): void
    {
        $this->assertSame(
            StatusEnum::getReturnableStatuses(),
            StatusEnum::getEditableStatuses()
        );
    }

    #[DataProvider('editableStatusProvider')]
    public function testIsEditableStatusReturnsTrueForEditableStatuses(string $status): void
    {
        $this->assertTrue(
            StatusEnum::isEditableStatus($status),
            sprintf('Expected status "%s" to be editable.', $status)
        );
    }

    public static function editableStatusProvider(): array
    {
        return [
            'queued status' => [StatusEnum::QUEUED->value],
            'notified status' => [StatusEnum::NOTIFIED->value],
            'picked up status' => [StatusEnum::PICKEDUP->value],
        ];
    }

    #[DataProvider('nonEditableStatusProvider')]
    public function testIsEditableStatusReturnsFalseForNonEditableStatuses(string $status): void
    {
        $this->assertFalse(
            StatusEnum::isEditableStatus($status),
            sprintf('Expected status "%s" to be non-editable.', $status)
        );
    }

    public static function nonEditableStatusProvider(): array
    {
        return [
            'expired status' => [StatusEnum::EXPIRED->value],
            'rejected status' => [StatusEnum::REJECTED->value],
            'unknown status' => ['unknown_status'],
        ];
    }
}
