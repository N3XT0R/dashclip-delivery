<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Resources\VideoResource\RelationManagers;

use App\Enum\StatusEnum;
use App\Filament\Standard\Resources\VideoResource\RelationManagers\AssignmentsRelationManager;
use App\Models\Assignment;
use App\Models\Download;
use ReflectionClass;
use Tests\DatabaseTestCase;

final class AssignmentsRelationManagerTest extends DatabaseTestCase
{
    public function testDownloadHelpersUseLatestDownloadInformation(): void
    {
        $assignment = Assignment::factory()->withBatch()->create([
            'status' => StatusEnum::NOTIFIED->value,
        ]);

        Download::factory()
            ->forAssignment($assignment)
            ->at(now()->subDay())
            ->create();

        Download::factory()
            ->forAssignment($assignment)
            ->at(now()->addMinute())
            ->create();

        $manager = new AssignmentsRelationManager($assignment);
        $reflection = new ReflectionClass($manager);

        $labelMethod = $reflection->getMethod('downloadLabel');
        $labelMethod->setAccessible(true);

        $iconMethod = $reflection->getMethod('downloadIcon');
        $iconMethod->setAccessible(true);

        $colorMethod = $reflection->getMethod('downloadColor');
        $colorMethod->setAccessible(true);

        $label = $labelMethod->invoke($manager, $assignment);
        $icon = $iconMethod->invoke($manager, $assignment);
        $color = $colorMethod->invoke($manager, $assignment);

        $this->assertStringContainsString((string) now()->addMinute()->isoFormat('DD.MM.YYYY HH:mm'), $label);
        $this->assertSame('heroicon-m-arrow-down-tray', $icon);
        $this->assertSame('success', $color);
    }

    public function testDownloadHelpersCoverRejectedAndExpiredAssignments(): void
    {
        $rejectedAssignment = Assignment::factory()->withBatch()->create([
            'status' => StatusEnum::REJECTED->value,
        ]);

        $expiredAssignment = Assignment::factory()->withBatch()->create([
            'status' => StatusEnum::EXPIRED->value,
        ]);

        $manager = new AssignmentsRelationManager();
        $reflection = new ReflectionClass($manager);

        $labelMethod = $reflection->getMethod('downloadLabel');
        $labelMethod->setAccessible(true);

        $iconMethod = $reflection->getMethod('downloadIcon');
        $iconMethod->setAccessible(true);

        $colorMethod = $reflection->getMethod('downloadColor');
        $colorMethod->setAccessible(true);

        $this->assertSame('Zurückgegeben', $labelMethod->invoke($manager, $rejectedAssignment));
        $this->assertSame('heroicon-m-arrow-uturn-left', $iconMethod->invoke($manager, $rejectedAssignment));
        $this->assertSame('warning', $colorMethod->invoke($manager, $rejectedAssignment));

        $this->assertSame('Abgelaufen', $labelMethod->invoke($manager, $expiredAssignment));
        $this->assertSame('heroicon-m-clock', $iconMethod->invoke($manager, $expiredAssignment));
        $this->assertSame('gray', $colorMethod->invoke($manager, $expiredAssignment));
    }

    public function testStatusHelpersExposeExpectedIconAndColorMappings(): void
    {
        $assignment = Assignment::factory()->withBatch()->create([
            'status' => StatusEnum::PICKEDUP->value,
        ]);

        $manager = new AssignmentsRelationManager();
        $reflection = new ReflectionClass($manager);

        $statusIconMethod = $reflection->getMethod('statusIcon');
        $statusIconMethod->setAccessible(true);

        $statusColorMethod = $reflection->getMethod('statusColor');
        $statusColorMethod->setAccessible(true);

        $this->assertSame('heroicon-m-check-circle', $statusIconMethod->invoke($manager, $assignment->status));
        $this->assertSame('primary', $statusColorMethod->invoke($manager, $assignment->status));

        $this->assertSame('heroicon-m-sparkles', $statusIconMethod->invoke($manager, StatusEnum::QUEUED->value));
        $this->assertSame('success', $statusColorMethod->invoke($manager, StatusEnum::NOTIFIED->value));

        $this->assertSame('heroicon-m-information-circle', $statusIconMethod->invoke($manager, 'unknown'));
        $this->assertSame('gray', $statusColorMethod->invoke($manager, 'unknown'));
    }
}
