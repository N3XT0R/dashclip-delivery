<?php

namespace App\Filament\Standard\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class OnboardingWizard extends Widget
{
    use HasWidgetShield;

    protected string $view = 'filament.standard.widgets.onboarding-wizard';
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->onboarding_completed === false;
    }
}
