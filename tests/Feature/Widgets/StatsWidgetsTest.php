<?php

declare(strict_types=1);

namespace Tests\Feature\Widgets;

use App\Enum\StatusEnum;
use App\Filament\Standard\Widgets\AvailableOffersStatsWidget;
use App\Filament\Standard\Widgets\DownloadedOffersStatsWidget;
use App\Filament\Standard\Widgets\ExpiredOffersStatsWidget;
use App\Models\Assignment;
use App\Services\Queries\AssignmentQueryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class StatsWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected AssignmentQueryInterface $query;

    protected function setUp(): void
    {
        parent::setUp();
        $this->query = app(AssignmentQueryInterface::class);
    }

    public function testAvailableWidgetCountsRecords(): void
    {
        Assignment::factory()->withBatch()->count(2)->queued()->create();
        $widget = App::make(AvailableOffersStatsWidget::class);

        $stats = $this->callGetStats($widget);

        $this->assertNotEmpty($stats);
    }

    public function testDownloadedWidgetHandlesPickups(): void
    {
        Assignment::factory()->withBatch()->state(['status' => StatusEnum::PICKEDUP->value])->create();
        $widget = App::make(DownloadedOffersStatsWidget::class);

        $stats = $this->callGetStats($widget);

        $this->assertNotEmpty($stats);
    }

    public function testExpiredWidgetCountsExpiredItems(): void
    {
        Assignment::factory()->withBatch()->state(['status' => StatusEnum::EXPIRED->value])->create();
        $widget = App::make(ExpiredOffersStatsWidget::class);

        $stats = $this->callGetStats($widget);

        $this->assertNotEmpty($stats);
    }

    private function callGetStats(object $widget): array
    {
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }
}
