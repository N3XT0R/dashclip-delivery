<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blog;

use App\Filament\Admin\Resources\Blog\PostTagResource\Pages;
use App\Models\PostTag;
use App\Models\PostTagTranslation;
use Filament\Actions\EditAction;
use Filament\Forms\Components\{TextInput, Select, Repeater};
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class PostTagResource extends Resource
{
    protected static ?string $model = PostTag::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Blog';

    public static function getModelLabel(): string
    {
        return __('blog.tags');
    }

    /** Configure the taxonomy and its locale-specific labels. */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')->required()->maxLength(180)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true),

            Repeater::make('translations')->label(__('blog.translations'))->relationship()->defaultItems(1)->minItems(1)->maxItems(2)->columnSpanFull()->schema([
                Select::make('locale')->options(['de' => 'Deutsch', 'en' => 'English'])->required()->distinct()->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->required()->maxLength(180)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(table: PostTagTranslation::class, ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('locale', $get('locale'))),

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
        return ['index' => Pages\ListPostTags::route('/'), 'create' => Pages\CreatePostTag::route('/create'), 'edit' => Pages\EditPostTag::route('/{record}/edit')];
    }
}
