<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Resources\VideoResource\Widgets;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Resources\VideoResource\Widgets\VideoStatsOverview;
use App\Models\Team;
use App\Models\User;
use App\Repository\AssignmentRepository;
use App\Repository\TeamRepository;
use App\Repository\VideoRepository;
use App\Services\VideoStatsService;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Mockery;
use Tests\DatabaseTestCase;

final class VideoStatsOverviewTest extends DatabaseTestCase
{
    private User $user;

    private Team $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->withOwnTeam()
            ->admin(GuardEnum::STANDARD)
            ->create();

        $this->tenant = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->tenant, true);
        Filament::auth()->login($this->user);
        $this->actingAs($this->user, GuardEnum::STANDARD->value);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testGetStatsUsesVideoStatsServiceForAuthenticatedUser(): void
    {
        $videoRepository = Mockery::mock(VideoRepository::class);
        $assignmentRepository = Mockery::mock(AssignmentRepository::class);

        $videoRepository->shouldReceive('getVideoCountForUser')
            ->once()
            ->with($this->user)
            ->andReturn(10);

        $assignmentRepository->shouldReceive('getPickedUpOffersCountForUser')
            ->once()
            ->with($this->user)
            ->andReturn(5);

        $assignmentRepository->shouldReceive('getAvailableOffersCountForUser')
            ->once()
            ->with($this->user)
            ->andReturn(3);

        $assignmentRepository->shouldReceive('getExpiredOffersCountForUser')
            ->once()
            ->with($this->user)
            ->andReturn(1);

        $service = new VideoStatsService($videoRepository, $assignmentRepository);

        $this->app->instance(VideoStatsService::class, $service);

        $widget = app(VideoStatsOverview::class);

        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        $this->assertCount(4, $stats);

        $this->assertSame('Videos', $stats[0]->getLabel());
        $this->assertSame('10', $stats[0]->getValue());

        $this->assertSame('Heruntergeladene Videos', $stats[1]->getLabel());
        $this->assertSame('5', $stats[1]->getValue());

        $this->assertSame('Verfügbare Offers', $stats[2]->getLabel());
        $this->assertSame('3', $stats[2]->getValue());

        $this->assertSame('Abgelaufene Offers', $stats[3]->getLabel());
        $this->assertSame('1', $stats[3]->getValue());
    }
}
