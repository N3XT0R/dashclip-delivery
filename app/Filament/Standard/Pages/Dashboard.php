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
        $widgets = [
            OnboardingWizard::class,
            AccountWidget::class,
        ];

        if (app()->environment('production', 'local')) {
            $widgets[] = StagingLinkWidget::class;
        }

        return $widgets;
    }
}
