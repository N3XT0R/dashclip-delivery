<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blog;

use App\Filament\Admin\Resources\Blog\PostResource\Pages;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Enum\Blog\PostStatusEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\{Select, TextInput, Textarea, FileUpload, MarkdownEditor, Repeater, DateTimePicker, Toggle};
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Blog';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    public static function getModelLabel(): string
    {
        return __('blog.posts');
    }

    /** Build the language-neutral article and independently publishable translations. */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('author_id')->label(__('blog.author'))->relationship('author', 'name')->searchable()->preload()->required()->default(auth()->id()),
            Select::make('category_id')->label(__('blog.category'))->relationship('category', 'slug')->searchable()->preload()->required(),
            Select::make('tags')->label(__('blog.tags'))->relationship('tags', 'slug')->multiple()->searchable()->preload(),
            FileUpload::make('image_path')->label(__('blog.image'))->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->disk('public')->directory('blog')->visibility('public')->maxSize(5120),
            Repeater::make('translations')->label(__('blog.translations'))->relationship()->defaultItems(1)->maxItems(2)
                ->helperText(__('blog.missing').': DE / EN')->columnSpanFull()->schema([
                    Select::make('locale')->options(['de' => 'Deutsch', 'en' => 'English'])->required()->distinct()->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                    TextInput::make('title')->label(__('blog.title_field'))->required()->maxLength(255),
                    TextInput::make('slug')->required()->maxLength(180)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->unique(table: PostTranslation::class, ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('locale', $get('locale'))),
                    Textarea::make('excerpt')->label(__('blog.excerpt'))->required()->maxLength(500),
                    MarkdownEditor::make('content')->label(__('blog.content'))->required()->maxLength(200000)->columnSpanFull()
                        ->fileAttachmentsDisk('public')->fileAttachmentsDirectory('blog/content')
                        ->helperText(__('blog.editor_help')),
                    Select::make('status')->label(__('blog.status'))->options(collect(PostStatusEnum::cases())->mapWithKeys(fn ($status) => [$status->value => __('blog.'.$status->value)]))->required()->default('draft')->live(),
                    DateTimePicker::make('published_at')->label(__('blog.publish_at'))->seconds(false)
                        ->required(fn (Get $get) => in_array($get('status'), ['published', 'scheduled'], true)),
                    TextInput::make('meta_title')->maxLength(255),
                    Textarea::make('meta_description')->maxLength(300),
                    TextInput::make('canonical_url')->url()->maxLength(255)->rules(['nullable', 'regex:~^https?://~i']),
                    Toggle::make('is_indexable')->default(true),
                ])->columns(2),
        ])->columns(2);
    }

    /** Show translation status and missing languages without per-row queries. */
    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['translations', 'category.translations', 'author']))
            ->columns([
                TextColumn::make('translations.title')->label(__('blog.title_field'))->listWithLineBreaks()->searchable(),
                TextColumn::make('category.slug')->label(__('blog.category')),
                TextColumn::make('author.display_name')->label(__('blog.author')),
                TextColumn::make('translation_status')->label(__('blog.status'))->state(fn (Post $record) =>
                    collect(['de', 'en'])->map(fn ($locale) => strtoupper($locale).': '.($record->translation($locale) ? __('blog.'.$record->translation($locale)->status->value) : __('blog.missing')))->implode(' / ')),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])->recordActions([EditAction::make()])->defaultSort('updated_at', 'desc');
    }

    /** @return array<string, mixed> Resource page registrations. */
    public static function getPages(): array
    {
        return ['index' => Pages\ListPosts::route('/'), 'create' => Pages\CreatePost::route('/create'), 'edit' => Pages\EditPost::route('/{record}/edit')];
    }
}
