<?php

namespace App\Filament\Standard\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Stringable;

class OnboardingWizard extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $title = 'Onboarding';

    protected string $view = 'filament.standard.pages.onboarding-wizard';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Wizard\Step::make('Willkommen')
                        ->schema([
                            TextEntry::make('welcome')
                                ->state($this->markdownFromView('onboarding.steps.welcome'))
                                ->markdown(),
                        ]),
                    Wizard\Step::make('Video-Upload')
                        ->schema([
                            TextEntry::make('video-upload')
                                ->state($this->markdownFromView('onboarding.steps.video-upload'))
                                ->markdown(),
                        ]),
                    Wizard\Step::make('Kanal-Auswahl')
                        ->schema([
                            TextEntry::make('channel-selection')
                                ->state($this->markdownFromView('onboarding.steps.channel-selection'))
                                ->markdown(),
                        ]),
                    Wizard\Step::make('Video-Management')
                        ->schema([
                            TextEntry::make('video-management')
                                ->state($this->markdownFromView('onboarding.steps.video-management'))
                                ->markdown(),
                        ]),
                    Wizard\Step::make('Fertig')
                        ->schema([
                            TextEntry::make('done')
                                ->state($this->markdownFromView('onboarding.steps.finished'))
                                ->markdown(),
                        ]),
                ])->submitAction(
                    Action::make('submit')
                        ->label('Onboarding abschließen'),
                ),
            ])
            ->statePath('data');
    }

    private function markdownFromView(string $view): Stringable
    {
        return str(view($view)->render())->trim();
    }

    public function submit()
    {
        $user = Auth::user();

        if ($user) {
            $user->forceFill(['onboarding_completed' => true])->save();
        }

        return redirect()->route(
            'filament.standard.pages.dashboard',
            ['tenant' => Filament::getTenant()]
        );
    }
}
