<?php

namespace App\Filament\Standard\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Support\Stringable;

class OnboardingWizard extends Widget implements HasForms
{
    use InteractsWithForms;
    use HasWidgetShield;

    protected static ?string $modalId = 'onboarding-wizard';
    protected string $view = 'filament.standard.widgets.onboarding-wizard';
    protected static bool $isLazy = false;

    public function mount(): void
    {
        if (static::canView()) {
            $this->dispatch('open-modal', id: static::$modalId);
        }
    }

    public static function canView(): bool
    {
        return auth()->user()?->onboarding_completed === false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([

                Wizard\Step::make('Willkommen')
                    ->schema([
                        TextEntry::make('intro')
                            ->state($this->markdownFromView('Onboarding.steps.willkommen'))
                            ->markdown(),
                    ]),

                Wizard\Step::make('Video-Upload')
                    ->schema([
                        TextEntry::make('video-upload')
                            ->state($this->markdownFromView('Onboarding.steps.video-upload'))
                            ->markdown(),
                    ]),

                Wizard\Step::make('Fertig')
                    ->schema([
                        TextEntry::make('done')
                            ->state($this->markdownFromView('Onboarding.steps.fertig'))
                            ->markdown(),
                    ]),

            ]),
        ]);
    }

    private function markdownFromView(string $view): Stringable
    {
        return str(view($view)->render())->trim();
    }

    public function submit(): void
    {
        auth()->user()->update(['onboarding_completed' => true]);

        Notification::make()
            ->title('Onboarding abgeschlossen')
            ->success()
            ->send();
    }
}
