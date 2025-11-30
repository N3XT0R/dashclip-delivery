<?php

namespace App\Filament\Standard\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Forms\Components\View as ViewField;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;

class Onboarding extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

    protected static ?string $slug = 'onboarding';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Onboarding';
    protected string $view = 'filament.standard.pages.onboarding';

    public ?array $data = [];

    public function mount(): void
    {
        if (auth()->user()?->onboarding_completed) {
            $this->redirect(
                route('filament.standard.pages.dashboard', ['tenant' => Filament::getTenant()?->getKey()]),
                navigate: true,
            );

            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Willkommen')
                        ->schema([
                            ViewField::make('intro')
                                ->view('onboarding.steps.welcome'),
                        ]),
                    Wizard\Step::make('Video-Upload')
                        ->schema([
                            ViewField::make('video-upload')
                                ->view('onboarding.steps.video-upload'),
                        ]),
                    Wizard\Step::make('Kanal-Auswahl')
                        ->schema([
                            ViewField::make('channel-selection')
                                ->view('onboarding.steps.channel-selection'),
                        ]),
                    Wizard\Step::make('Video-Management')
                        ->schema([
                            ViewField::make('video-management')
                                ->view('onboarding.steps.video-management'),
                        ]),
                    Wizard\Step::make('Fertig')
                        ->schema([
                            ViewField::make('done')
                                ->view('onboarding.steps.finished'),
                        ]),
                ])
                    ->submitActionAlignment(Alignment::Right)
                    ->skippable(false),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        auth()->user()->update(['onboarding_completed' => true]);

        Notification::make()
            ->title('Onboarding abgeschlossen')
            ->success()
            ->send();

        $this->redirect(
            route('filament.standard.pages.dashboard', ['tenant' => Filament::getTenant()?->getKey()]),
            navigate: true,
        );
    }
}
