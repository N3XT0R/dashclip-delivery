<?php

namespace App\Filament\Admin\Resources\Videos\Pages;

use App\Filament\Admin\Resources\Videos\VideoResource;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Group;
use Illuminate\Support\Number;

class ViewVideo extends ViewRecord
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Group::make()
                ->schema([
                    TextInput::make('original_name')->label(__('filament.admin.labels.file_name'))->disabled(),
                    TextInput::make('ext')->disabled(),
                    TextInput::make('bytes')->label(__('filament.admin.labels.size'))->disabled()
                        ->formatStateUsing(fn (
                            $state
                        ) => $state ? Number::fileSize((int)$state) : '–'),
                    TextInput::make('disk')->disabled(),
                    TextInput::make('hash')->disabled(),
                    KeyValue::make('meta')->label(__('filament.admin.labels.meta'))->disabled()->columnSpanFull(),
                ]),
        ];
    }
}
