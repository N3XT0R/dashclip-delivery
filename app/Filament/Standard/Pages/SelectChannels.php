<?php

namespace App\Filament\Standard\Pages;

use App\Models\Channel;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;

class SelectChannels extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $title = 'Kanäle auswählen';
    protected static ?string $navigationLabel = 'Channels';
    protected string $view = 'filament.standard.pages.select-channels';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected function getFormSchema(): array
    {
        return [
            Select::make('channels')
                ->label('Verfügbare Kanäle')
                ->options(
                    Channel::pluck('name', 'id')
                )
                ->multiple()
                ->default(auth()->user()->assignedChannels()->pluck('channel_id'))
        ];
    }

    public function submit()
    {
        auth()->user()->assignedChannels()->sync(
            $this->form->getState()['channels'] ?? []
        );

        $this->notify('success', 'Kanäle gespeichert.');
    }
}
