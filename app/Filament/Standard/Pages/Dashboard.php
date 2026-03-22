<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages;

use App\Filament\Standard\Widgets\OnboardingWizard;
use App\Filament\Standard\Widgets\StagingLinkWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            OnboardingWizard::class,
            StagingLinkWidget::class,
            AccountWidget::class,
        ];
    }
}
