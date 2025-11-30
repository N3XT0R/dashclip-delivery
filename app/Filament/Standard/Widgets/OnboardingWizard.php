<?php

namespace App\Filament\Standard\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OnboardingWizard extends Widget
{
    use HasWidgetShield;

    public bool $shouldOpen = false;

    protected string $view = 'filament.standard.widgets.onboarding-wizard';

    protected static bool $isLazy = false;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user && $user->onboarding_completed === false) {
            redirect()
                ->route('filament.standard.pages.onboarding-wizard')
                ->send();
        }
    }
}
