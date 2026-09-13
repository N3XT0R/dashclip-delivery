<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blog;

use App\Filament\Admin\Resources\Blog\PostCategoryResource\Pages;
use App\Models\PostCategory;
use App\Models\PostCategoryTranslation;
use Filament\Actions\EditAction;
use Filament\Forms\Components\{TextInput, Textarea, Select, Repeater};
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class PostCategoryResource extends Resource
{
    protected static ?string $model = PostCategory::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Blog';

    public static function getModelLabel(): string
    {
        return __('blog.categories');
    }

    /** Configure the taxonomy and its locale-specific labels. */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')->required()->maxLength(180)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true),
            Select::make('icon')->options(['video' => 'Video', 'shield' => 'Sicherheit', 'camera' => 'Kamera', 'news' => 'News', 'settings' => 'Technik']),
            Repeater::make('translations')->label(__('blog.translations'))->relationship()->defaultItems(1)->maxItems(2)->schema([
                Select::make('locale')->options(['de' => 'Deutsch', 'en' => 'English'])->required()->distinct()->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->required()->maxLength(180)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(table: PostCategoryTranslation::class, ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('locale', $get('locale'))),
                Textarea::make('description')->maxLength(1000),
            ]),
        ]);
    }

    /** List taxonomy labels with their translations already loaded. */
    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('translations'))
            ->columns([TextColumn::make('slug')->searchable(), TextColumn::make('translations.name')->listWithLineBreaks()])
            ->recordActions([EditAction::make()]);
    }

    /** @return array<string, mixed> Resource page registrations. */
    public static function getPages(): array
    {
        return ['index' => Pages\ListPostCategories::route('/'), 'create' => Pages\CreatePostCategory::route('/create'), 'edit' => Pages\EditPostCategory::route('/{record}/edit')];
    }
}
