<?php

namespace App\Filament\Standard\Pages;

use App\Repository\ChannelRepository;
use App\Services\ChannelService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class ChannelApplication extends Page implements HasForms
{

    use InteractsWithForms;

    protected string $view = 'filament.standard.pages.channel-application';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencil;

    protected static ?string $title = 'filament.channel_application.title';
    protected static ?string $navigationLabel = 'filament.channel_application.navigation_label';
    protected static string|UnitEnum|null $navigationGroup = 'filament.channel_application.navigation_group';

    public ?array $data = [];

    public function getTitle(): string|Htmlable
    {
        return __(static::$title);
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function getNavigationGroup(): string
    {
        return __(static::$navigationGroup);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('channel_id')
                    ->label('Channel')
                    ->options(function () {
                        return app(ChannelRepository::class)->getActiveChannels()->pluck('name', 'id')->toArray();
                    })
                    ->required(),
                Textarea::make('note')
                    ->label('filament.channel_application.form.note_label')
                    ->translateLabel()
                    ->maxLength(255)
                    ->rows(5)
                    ->placeholder(__('filament.channel_application.form.note_placeholder')),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            app(ChannelService::class)->applyForAccess($data, auth()->user());
            Notification::make()
                ->title(__('filament.channel_application.messages.application_submitted'))
                ->success()
                ->send();
            $this->form->fill([]);
        } catch (\DomainException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
