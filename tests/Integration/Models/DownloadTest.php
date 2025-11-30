<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Assignment;
use App\Models\Download;
use Tests\DatabaseTestCase;

final class DownloadTest extends DatabaseTestCase
{
    public function testBelongsToAssignment(): void
    {
        $assignment = Assignment::factory()->withBatch()->create();

        $download = Download::factory()->create([
            'assignment_id' => $assignment->getKey(),
        ]);

        $this->assertTrue($download->assignment->is($assignment));
    }
}
