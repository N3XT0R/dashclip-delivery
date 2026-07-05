<?php

namespace App\Filament\Admin\Resources\Channels;

use App\Filament\Admin\Resources\Channels\Pages\CreateChannel;
use App\Filament\Admin\Resources\Channels\Pages\EditChannel;
use App\Filament\Admin\Resources\Channels\Pages\ListChannels;
use App\Models\Channel;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChannelResource extends Resource
{
    protected static ?string $model = Channel::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';
    protected static string|\UnitEnum|null $navigationGroup = 'filament.admin.navigation.media';
    protected static ?string $modelLabel = 'filament.admin.labels.channel';
    protected static ?string $pluralModelLabel = 'filament.admin.labels.channels';

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('filament.admin.navigation.media');
    }

    public static function getModelLabel(): string
    {
        return __('filament.admin.labels.channel');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.admin.labels.channels');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('creator_name')
                ->label(__('filament.admin.labels.creator'))
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->maxLength(255),
            TextInput::make('youtube_name')
                ->maxLength(255)
                ->required(),
            TextInput::make('weight')
                ->numeric(),
            TextInput::make('weekly_quota')
                ->label(__('filament.admin.labels.weekly_quota'))
                ->numeric(),
            Checkbox::make('is_video_reception_paused')
                ->label(__('filament.admin.labels.pause_video_reception'))
                ->default(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('creator_name')
                    ->label(__('filament.admin.labels.creator'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('youtube_name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('weight')
                    ->sortable(),
                TextColumn::make('weekly_quota')
                    ->label(__('filament.admin.labels.weekly_quota'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime('Y-m-d H:i')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                IconColumn::make('is_video_reception_paused')
                    ->label(__('filament.admin.labels.paused_video_reception'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChannels::route('/'),
            'create' => CreateChannel::route('/create'),
            'edit' => EditChannel::route('/{record}/edit'),
        ];
    }
}
