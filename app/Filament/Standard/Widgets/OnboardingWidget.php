<?php

namespace App\Filament\Standard\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OnboardingWidget extends Widget
{
    public bool $shouldOpen = false;

    protected string $view = 'filament.standard.widgets.onboarding-wizard';

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
