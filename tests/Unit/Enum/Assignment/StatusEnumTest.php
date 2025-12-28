<?php

declare(strict_types=1);

namespace Tests\Unit\Enum\Assignment;

use App\Enum\StatusEnum;
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

    public function testIsEditableStatusReturnsTrueForEditableStatuses(): void
    {
        foreach (StatusEnum::getEditableStatuses() as $status) {
            $this->assertTrue(
                StatusEnum::isEditableStatus($status),
                sprintf('Expected status "%s" to be editable.', $status)
            );
        }
    }

    public function testIsEditableStatusReturnsFalseForNonEditableStatuses(): void
    {
        $nonEditableStatuses = [
            StatusEnum::EXPIRED->value,
            StatusEnum::REJECTED->value,
            'unknown_status',
        ];

        foreach ($nonEditableStatuses as $status) {
            $this->assertFalse(
                StatusEnum::isEditableStatus($status),
                sprintf('Expected status "%s" to be non-editable.', $status)
            );
        }
    }
}
