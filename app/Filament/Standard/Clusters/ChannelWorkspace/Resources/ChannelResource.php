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

class ChannelResource extends Resource
{
    protected static ?string $model = Channel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = ChannelWorkspace::class;

    protected static ?string $recordTitleAttribute = 'Panel ';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('creator_name'),
                Forms\Components\TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('youtube_name'),
                Forms\Components\TextInput::make('weight')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('weekly_quota')
                    ->required()
                    ->numeric()
                    ->default(5),
                Forms\Components\Toggle::make('is_video_reception_paused')
                    ->required(),
                Forms\Components\DateTimePicker::make('approved_at'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('name'),
                Infolists\Components\TextEntry::make('creator_name')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('email')
                    ->label('Email address'),
                Infolists\Components\TextEntry::make('youtube_name')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('weight')
                    ->numeric(),
                Infolists\Components\TextEntry::make('weekly_quota')
                    ->numeric(),
                Infolists\Components\IconEntry::make('is_video_reception_paused')
                    ->boolean(),
                Infolists\Components\TextEntry::make('approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Panel ')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('creator_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('youtube_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('weight')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weekly_quota')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_video_reception_paused')
                    ->boolean(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'create' => Pages\CreateChannel::route('/create'),
            'view' => Pages\ViewChannel::route('/{record}'),
            'edit' => Pages\EditChannel::route('/{record}/edit'),
        ];
    }
}
