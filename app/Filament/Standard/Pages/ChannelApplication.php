<?php

namespace App\Filament\Standard\Pages;

use App\DTO\ChannelApplicationRequestDto;
use App\Repository\ChannelRepository;
use App\Services\ChannelService;
use BackedEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                Checkbox::make('other_channel_request')
                    ->label(__('filament.channel_application.form.request_other_channel'))
                    ->reactive(),
                Select::make('channel_id')
                    ->label('Channel')
                    ->options(function () {
                        return app(ChannelRepository::class)->getActiveChannels()->pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->hidden(fn($get) => $get('other_channel_request'))
                    ->required(fn($get) => !$get('other_channel_request')),
                TextInput::make('new_channel_name')
                    ->label(__('filament.channel_application.form.new_channel_name_label'))
                    ->placeholder(__('filament.channel_application.form.new_channel_name_placeholder'))
                    ->visible(fn($get) => $get('other_channel_request'))
                    ->required(fn($get) => $get('other_channel_request')),

                TextInput::make('new_channel_slug')
                    ->label(__('filament.channel_application.form.new_channel_slug_label'))
                    ->placeholder(__('filament.channel_application.form.new_channel_slug_placeholder'))
                    ->visible(fn($get) => $get('other_channel_request'))
                    ->required(fn($get) => $get('other_channel_request')),

                TextInput::make('new_channel_email')
                    ->label(__('filament.channel_application.form.new_channel_email_label'))
                    ->type('email')
                    ->placeholder(__('filament.channel_application.form.new_channel_email_placeholder'))
                    ->visible(fn($get) => $get('other_channel_request'))
                    ->required(fn($get) => $get('other_channel_request')),

                Textarea::make('new_channel_description')
                    ->label(__('filament.channel_application.form.new_channel_description_label'))
                    ->placeholder(__('filament.channel_application.form.new_channel_description_placeholder'))
                    ->rows(4)
                    ->visible(fn($get) => $get('other_channel_request'))
                    ->required(fn($get) => $get('other_channel_request')),
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
        $state = $this->form->getState();
        $dto = new ChannelApplicationRequestDto(
            channelId: $state['channel_id'] ?? null,
            note: $state['note'] ?? '',
            otherChannelRequest: $state['other_channel_request'] ?? false,
            newChannelName: $state['new_channel_name'] ?? null,
            newChannelSlug: $state['new_channel_slug'] ?? null,
            newChannelEmail: $state['new_channel_email'] ?? null,
            newChannelDescription: $state['new_channel_description'] ?? null,
        );

        try {
            app(ChannelService::class)->applyForAccess($dto, auth()->user());
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
