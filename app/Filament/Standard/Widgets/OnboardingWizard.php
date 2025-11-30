<?php

namespace App\Filament\Standard\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OnboardingWizard extends Widget
{
    use HasWidgetShield;

    public bool $shouldOpen = false;

    protected string $view = 'filament.standard.widgets.onboarding-wizard';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user ? $user->onboarding_completed === false : false;
    }

    public function mount()
    {
        $user = Auth::user();

        if ($user && $user->onboarding_completed === false) {
            return redirect()->route('filament.standard.pages.onboarding-wizard', [
                'tenant' => Filament::getTenant(),
            ]);
        }
    }
}
