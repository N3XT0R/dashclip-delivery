<?php

namespace App\Filament\Standard\Pages;

use App\Models\User;
use App\Repository\ChannelRepository;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class SelectChannels extends Page
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $title = 'Kanäle auswählen';
    protected static ?string $navigationLabel = 'Channels';
    protected string $view = 'filament.standard.pages.select-channels';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    public ?array $channels = [];

    public function mount(ChannelRepository $channelRepository): void
    {
        /**
         * @var User $user
         */
        $user = Filament::auth()->user();
        $this->channels = $channelRepository->getUserAssignedChannels($user)->pluck('channel_id')->toArray();
    }

    public function form(Schema $schema): Schema
    {
        $repository = app(ChannelRepository::class);
        return $schema
            ->schema([
                Select::make('channels')
                    ->label('Verfügbare Kanäle')
                    ->options($repository->getActiveChannels()->pluck('name', 'id'))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->statePath('channels');
    }

    public function save(): void
    {
        auth()->user()->assignedChannels()->sync($this->channels ?? []);

        Notification::make()
            ->title('Kanäle gespeichert')
            ->success()
            ->send();
    }
}
