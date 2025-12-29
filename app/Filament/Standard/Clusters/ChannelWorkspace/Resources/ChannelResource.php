<?php

namespace App\Filament\Standard\Clusters\ChannelWorkspace\Resources;

use App\Filament\Standard\Clusters\ChannelWorkspace\ChannelWorkspace;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages;
use App\Models\Channel;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChannelResource extends Resource
{
    protected static ?string $model = Channel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = ChannelWorkspace::class;

    public static function getRecordTitleAttribute(): ?string
    {
        return __('common.channel');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('creator_name')
                    ->label(__('channel-workspace.channel_resource.creator_name')),
                Forms\Components\TextInput::make('email')
                    ->label(__('common.email'))
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('youtube_name')
                    ->label(__('common.youtube_name')),
                Forms\Components\Toggle::make('is_video_reception_paused')
                    ->label(__('common.is_video_reception_paused'))
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('name'),
                Infolists\Components\TextEntry::make('email')
                    ->label(__('common.email')),
                Infolists\Components\TextEntry::make('youtube_name')
                    ->formatStateUsing(fn($state) => $state ? '@' . $state : '-')
                    ->url(function (Channel $record) {
                        if ($record->youtube_name) {
                            return 'https://www.youtube.com/@' . $record->youtube_name;
                        }

                        return null;
                    }, true)
                    ->placeholder('-'),
                Infolists\Components\IconEntry::make('is_video_reception_paused')
                    ->label(__('common.is_video_reception_paused'))
                    ->boolean(),
                Infolists\Components\TextEntry::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('updated_at')
                    ->label(__('common.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(self::getRecordTitleAttribute())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('youtube_name')
                    ->label(__('common.youtube_name'))
                    ->formatStateUsing(fn($state) => $state ? '@' . $state : '-')
                    ->url(function (Channel $record) {
                        if ($record->youtube_name) {
                            return 'https://www.youtube.com/@' . $record->youtube_name;
                        }

                        return null;
                    }, true)
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_video_reception_paused')
                    ->label(__('common.is_video_reception_paused'))
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChannels::route('/'),
            'view' => Pages\ViewChannel::route('/{record}'),
            'edit' => Pages\EditChannel::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /**
         * @var Builder<Channel> $parent
         */
        $parent = parent::getEloquentQuery();

        return $parent->userHasAccess(auth()->user());
    }
}
