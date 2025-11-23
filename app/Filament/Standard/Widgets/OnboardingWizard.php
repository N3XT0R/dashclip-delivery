<?php

namespace App\Filament\Standard\Widgets;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;

class OnboardingWizard extends Widget
{
    use InteractsWithForms;

    protected static ?string $modalId = 'onboarding-wizard';
    protected string $view = 'filament.standard.widgets.onboarding-wizard';
    protected static bool $isLazy = false;

    public static function shouldRegister(): bool
    {
        return auth()->user()?->onboarding_completed === false;
    }

    protected function heading(): string
    {
        return 'Willkommen! Lass uns dein Profil einrichten';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([

                Wizard\Step::make('Willkommen')
                    ->schema([
                        MarkdownEditor::make('intro')->content('So funktioniert DashClip ...'),
                    ]),

                Wizard\Step::make('Video-Upload')
                    ->schema([]),

                Wizard\Step::make('Fertig')
                    ->schema([
                        TextEntry::make('done')
                            ->state('Alles bereit – bestätige zum Abschluss.'),
                    ]),

            ]),
        ]);
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
