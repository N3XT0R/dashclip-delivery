<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Enum\MailDirection;
use App\Enum\MailStatus;
use App\Models\MailLog;
use Tests\DatabaseTestCase;

final class MailLogTest extends DatabaseTestCase
{
    public function testCastsEnumerationsAndMeta(): void
    {
        $log = MailLog::factory()->create([
            'direction' => MailDirection::INBOUND,
            'status' => MailStatus::Sent,
            'meta' => ['attempts' => 1],
        ]);

        $this->assertInstanceOf(MailDirection::class, $log->direction);
        $this->assertInstanceOf(MailStatus::class, $log->status);
        $this->assertSame(['attempts' => 1], $log->meta);
    }
}
