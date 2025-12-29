<?php

namespace App\Filament\Resources;

use App\Enum\Channel\ApplicationEnum;
use App\Filament\Resources\ChannelApplicationResource\Pages;
use App\Models\ChannelApplication;
use App\Repository\ChannelRepository;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class ChannelApplicationResource extends Resource
{
    protected static ?string $model = ChannelApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;


    public static function getPluralLabel(): ?string
    {
        return __('filament.admin_channel_application.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin_channel_application.navigation_group');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return app(ChannelRepository::class)->getChannelApplicationPendingAmount();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label('filament.admin_channel_application.table.columns.applicant')
                    ->translateLabel()
                    ->relationship('user', 'name')
                    ->disabled()
                    ->required(),
                Forms\Components\TextInput::make('user_email')
                    ->formatStateUsing(fn(ChannelApplication $record) => $record->user->email)
                    ->label('filament.admin_channel_application.form.fields.user_email')
                    ->disabled()
                    ->translateLabel(),
                Forms\Components\Select::make('status')
                    ->label('filament.admin_channel_application.table.columns.status')
                    ->translateLabel()
                    ->options(function () {
                        return collect(ApplicationEnum::all())->mapWithKeys(function ($value) {
                            return [$value => __('filament.admin_channel_application.status.' . $value)];
                        });
                    })
                    ->disabled(fn($record) => ApplicationEnum::APPROVED->value === $record->status),
                Section::make('existing_channel')
                    ->heading(false)
                    ->hidden(fn($record) => filled($record->meta->channel['name'] ?? null))
                    ->label('filament.admin_channel_application.form.sections.existing_channel')
                    ->translateLabel()
                    ->schema([
                        Forms\Components\Select::make('channel_id')
                            ->label('filament.admin_channel_application.table.columns.channel')
                            ->translateLabel()
                            ->relationship('channel', 'name'),
                    ])
                    ->columnSpanFull(),
                Section::make('new_channel')
                    ->heading(false)
                    ->hidden(fn($record) => !filled($record->meta->channel['name'] ?? null))
                    ->label('filament.admin_channel_application.form.sections.new_channel')
                    ->translateLabel()
                    ->schema([
                        Forms\Components\TextInput::make('meta.new_channel.name')
                            ->label('filament.admin_channel_application.form.fields.new_channel_name_label')
                            ->translateLabel()
                            ->disabled(),
                        Forms\Components\TextInput::make('meta.new_channel.creator_name')
                            ->label('filament.admin_channel_application.form.fields.new_channel_creator_name_label')
                            ->translateLabel()
                            ->disabled(),
                        Forms\Components\TextInput::make('meta.new_channel.email')
                            ->label('filament.admin_channel_application.form.fields.new_channel_email_label')
                            ->translateLabel()
                            ->disabled(),
                        Forms\Components\TextInput::make('meta.new_channel.youtube_name')
                            ->label('filament.admin_channel_application.form.fields.new_channel_youtube_name_label')
                            ->translateLabel()
                            ->disabled(),
                    ])
                    ->columnSpanFull(),
                TextEntry::make('note')
                    ->label('filament.admin_channel_application.form.fields.note')
                    ->translateLabel()
                    ->markdown()
                    ->columnSpanFull(),
                Forms\Components\MarkdownEditor::make('meta.reject_reason')
                    ->label('filament.admin_channel_application.form.fields.reason')
                    ->translateLabel()
                    ->disableToolbarButtons(['attachFiles'])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Channel Application')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('filament.admin_channel_application.table.columns.applicant')
                    ->translateLabel()
                    ->searchable(),
                Tables\Columns\TextColumn::make('channel.name')
                    ->label('filament.admin_channel_application.table.columns.channel')
                    ->translateLabel()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('filament.admin_channel_application.table.columns.status')
                    ->formatStateUsing(function ($state) {
                        return __('filament.channel_application.status.' . strtolower($state));
                    })
                    ->translateLabel()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('filament.admin_channel_application.table.columns.submitted_at')
                    ->translateLabel()
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('filament.admin_channel_application.table.columns.updated_at')
                    ->translateLabel()
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options(ApplicationEnum::all())
                    ->default([ApplicationEnum::PENDING])
            ])
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChannelApplications::route('/'),
            'edit' => Pages\EditChannelApplication::route('/{record}/edit'),
        ];
    }
}
