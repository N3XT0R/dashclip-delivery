<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Standard\Pages;

use App\Filament\Standard\Pages\Dashboard;
use App\Filament\Standard\Widgets\OnboardingWizard;
use Filament\Widgets\AccountWidget;
use Tests\TestCase;

class DashboardTest extends TestCase
{

    public function testGetWidgetsReturnsConfiguredWidgets(): void
    {
        $dashboard = new Dashboard();
        $widgets = $dashboard->getWidgets();
        $this->assertSame([OnboardingWizard::class, AccountWidget::class], $widgets);
    }
}
