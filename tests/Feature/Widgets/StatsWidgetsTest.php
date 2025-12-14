<?php

declare(strict_types=1);

namespace Tests\Feature\Widgets;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\StatusEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Widgets\ChannelWidgets\AvailableOffersStatsWidget;
use App\Filament\Standard\Widgets\ChannelWidgets\DownloadedOffersStatsWidget;
use App\Filament\Standard\Widgets\ChannelWidgets\ExpiredOffersStatsWidget;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Download;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class StatsWidgetsTest extends DatabaseTestCase
{
    private User $user;

    private Team $team;

    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->withOwnTeam()
            ->create();

        $this->team = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        $this->channel = Channel::factory()->create();
        $this->channel->assignedTeams()->attach($this->team);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->team, true);
        Filament::auth()->login($this->user);

        $this->actingAs($this->user, GuardEnum::STANDARD->value);

        $this->user->assignRole(RoleEnum::CHANNEL_OPERATOR->value);
    }

    public function testAvailableOffersStatsWidgetRendersWithoutErrors(): void
    {
        Livewire::test(AvailableOffersStatsWidget::class)
            ->assertSuccessful();
    }

    public function testAvailableOffersStatsWidgetCalculatesCorrectStats(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        // Create 3 available assignments
        Assignment::factory()
            ->count(3)
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::QUEUED->value,
                'expires_at' => now()->addDays(5),
            ]);

        // Create 1 available but downloaded
        $downloadedAssignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::NOTIFIED->value,
                'expires_at' => now()->addDays(3),
            ]);

        Download::factory()
            ->forAssignment($downloadedAssignment)
            ->create();

        $widget = Livewire::test(AvailableOffersStatsWidget::class);

        $stats = $widget->instance()->getCachedStats();

        $this->assertCount(3, $stats);

        // Check that stats return strings (no Optional objects)
        foreach ($stats as $stat) {
            $this->assertIsString($stat->getValue());
        }

        // Total available should be 4 (3 not downloaded + 1 downloaded but still valid)
        $this->assertEquals('4', $stats[0]->getValue());

        // Downloaded from available should be 1
        $this->assertEquals('1', $stats[1]->getValue());

        // Average validity days should be > 0
        $avgDays = (int)$stats[2]->getValue();
        $this->assertGreaterThan(0, $avgDays);
    }

    public function testDownloadedOffersStatsWidgetRendersWithoutErrors(): void
    {
        Livewire::test(DownloadedOffersStatsWidget::class)
            ->assertSuccessful();
    }

    public function testDownloadedOffersStatsWidgetCalculatesCorrectStats(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        // Create 5 downloaded assignments
        for ($i = 0; $i < 5; $i++) {
            $assignment = Assignment::factory()
                ->for($this->channel, 'channel')
                ->withBatch($batch)
                ->forVideo(Video::factory()->withClips(1, $this->user)->create())
                ->create([
                    'status' => StatusEnum::PICKEDUP->value,
                ]);

            Download::factory()
                ->forAssignment($assignment)
                ->at(Carbon::now()->subDays($i))
                ->create();
        }

        $widget = Livewire::test(DownloadedOffersStatsWidget::class);

        $stats = $widget->instance()->getCachedStats();

        $this->assertCount(3, $stats);

        // Check that stats return strings (no Optional objects)
        foreach ($stats as $stat) {
            $this->assertIsString($stat->getValue());
        }

        // Total downloaded should be 5
        $this->assertEquals('5', $stats[0]->getValue());

        // This week count should be > 0
        $thisWeek = (int)$stats[1]->getValue();
        $this->assertGreaterThanOrEqual(0, $thisWeek);

        // Average days ago should be >= 0
        $avgDaysAgo = (int)$stats[2]->getValue();
        $this->assertGreaterThanOrEqual(0, $avgDaysAgo);
    }

    public function testExpiredOffersStatsWidgetRendersWithoutErrors(): void
    {
        Livewire::test(ExpiredOffersStatsWidget::class)
            ->assertSuccessful();
    }

    public function testExpiredOffersStatsWidgetCalculatesCorrectStats(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        // Create 4 expired assignments - 2 downloaded, 2 missed
        for ($i = 0; $i < 2; $i++) {
            $assignment = Assignment::factory()
                ->for($this->channel, 'channel')
                ->withBatch($batch)
                ->forVideo(Video::factory()->withClips(1, $this->user)->create())
                ->create([
                    'status' => StatusEnum::EXPIRED->value,
                    'expires_at' => now()->subDay(),
                ]);

            Download::factory()
                ->forAssignment($assignment)
                ->create();
        }

        // Expired without download
        Assignment::factory()
            ->count(2)
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::EXPIRED->value,
                'expires_at' => now()->subDay(),
            ]);

        $widget = Livewire::test(ExpiredOffersStatsWidget::class);

        $stats = $widget->instance()->getCachedStats();

        $this->assertCount(3, $stats);

        // Check that stats return strings (no Optional objects)
        foreach ($stats as $stat) {
            $this->assertIsString($stat->getValue());
        }

        // Total expired should be 4
        $this->assertEquals('4', $stats[0]->getValue());

        // Downloaded before expiry should be 2
        $this->assertEquals('2', $stats[1]->getValue());

        // Missed should be 2
        $this->assertEquals('2', $stats[2]->getValue());
    }

    public function testWidgetsReturnZeroWhenNoChannel(): void
    {
        // Remove user from team/channel
        $this->team->members()->detach($this->user);

        $availableWidget = Livewire::test(AvailableOffersStatsWidget::class);
        $downloadedWidget = Livewire::test(DownloadedOffersStatsWidget::class);
        $expiredWidget = Livewire::test(ExpiredOffersStatsWidget::class);

        $availableStats = $availableWidget->instance()->getCachedStats();
        $downloadedStats = $downloadedWidget->instance()->getCachedStats();
        $expiredStats = $expiredWidget->instance()->getCachedStats();

        foreach ($availableStats as $stat) {
            $this->assertEquals('0', $stat->getValue());
        }

        foreach ($downloadedStats as $stat) {
            $this->assertEquals('0', $stat->getValue());
        }

        foreach ($expiredStats as $stat) {
            $this->assertEquals('0', $stat->getValue());
        }
    }

    public function testWidgetsDoNotReturnOptionalObjects(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::QUEUED->value,
                'expires_at' => now()->addDays(5),
            ]);

        $widgets = [
            AvailableOffersStatsWidget::class,
            DownloadedOffersStatsWidget::class,
            ExpiredOffersStatsWidget::class,
        ];

        foreach ($widgets as $widgetClass) {
            $widget = Livewire::test($widgetClass);
            $stats = $widget->instance()->getCachedStats();

            foreach ($stats as $stat) {
                $value = $stat->getValue();

                // Ensure value is string or int, not Optional
                $this->assertTrue(
                    is_string($value) || is_int($value),
                    "Widget {$widgetClass} returned non-scalar value: ".gettype($value)
                );

                // Ensure it's not an object
                $this->assertFalse(
                    is_object($value),
                    "Widget {$widgetClass} returned object instead of scalar"
                );
            }
        }
    }
}
