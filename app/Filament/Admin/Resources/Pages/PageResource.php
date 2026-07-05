<?php

namespace App\Filament\Admin\Resources\Pages;

use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Filament\Admin\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = 'filament.admin.navigation.content';
    protected static ?string $modelLabel = 'filament.admin.labels.page';
    protected static ?string $pluralModelLabel = 'filament.admin.labels.pages';

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('filament.admin.navigation.content');
    }

    public static function getModelLabel(): string
    {
        return __('filament.admin.labels.page');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.admin.labels.pages');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            TextInput::make('section')
                ->label(__('filament.admin.labels.section'))
                ->required()
                ->maxLength(255)
                ->disabled(),
            MarkdownEditor::make('content')
                ->label(__('filament.admin.labels.content'))
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('section')
                    ->label(__('filament.admin.labels.section'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->dateTime('Y-m-d H:i')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            //'create' => Pages\CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
