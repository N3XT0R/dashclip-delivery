<?php

namespace App\Filament\Admin\Resources\Videos;

use App\Application\Clips\GetPreviewUrl;
use App\Enum\Users\RoleEnum;
use App\Filament\Admin\Resources\Videos\Pages\ListVideos;
use App\Filament\Admin\Resources\Videos\Pages\ViewVideo;
use App\Filament\Admin\Resources\Videos\RelationManagers\AssignmentsRelationManager;
use App\Filament\Admin\Resources\Videos\RelationManagers\ClipsRelationManager;
use App\Models\Video;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-film';
    protected static string|\UnitEnum|null $navigationGroup = 'filament.admin.navigation.media';
    protected static ?string $modelLabel = 'filament.admin.labels.video';
    protected static ?string $pluralModelLabel = 'filament.admin.labels.videos';

    protected static bool $isScopedToTenant = false;

    public static function getModelLabel(): string
    {
        return __('filament.admin.labels.video');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.admin.labels.videos');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('original_name')
                    ->label(__('filament.admin.labels.file_name'))
                    ->searchable()
                    ->wrap(false)
                    ->limit(40),

                TextColumn::make('ext')
                    ->badge()
                    ->sortable()
                    ->label(__('filament.admin.labels.ext')),

                TextColumn::make('bytes')
                    ->label(__('filament.admin.labels.size'))
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Number::fileSize((int)$state) : '–'),

                TextColumn::make('disk')
                    ->sortable()
                    ->toggleable()
                    ->label(__('filament.admin.labels.disk')),
                TextColumn::make('assignments_count')
                    ->counts('assignments')
                    ->label(__('filament.admin.labels.assignments')),
                TextColumn::make('clips.user.display_name')
                    ->label(__('filament.admin.labels.submitted_account'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('clips.user', function (Builder $userQuery) use ($search) {
                            $userQuery->where('submitted_name', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                    })
                    ->default('-'),

                TextColumn::make('clips.submitted_by')
                    ->sortable()
                    ->searchable()
                    ->label(__('filament.admin.labels.submitted_by')),

                TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->label(__('filament.admin.labels.created')),
            ])
            ->filters([
                SelectFilter::make('disk')
                    ->label(__('filament.admin.labels.disk'))
                    ->options(fn () => Video::query()
                        ->select('disk')->whereNotNull('disk')->distinct()->pluck('disk', 'disk')->toArray()),

                SelectFilter::make('ext')
                    ->label(__('filament.admin.labels.ext'))
                    ->options(fn () => Video::query()
                        ->select('ext')->whereNotNull('ext')->distinct()->pluck('ext', 'ext')->toArray()),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label(__('filament.admin.labels.from')),
                        DatePicker::make('until')->label(__('filament.admin.labels.to')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('preview')
                    ->label(__('filament.admin.labels.preview'))
                    ->icon('heroicon-m-play')
                    ->url(fn (Video $video) => app(GetPreviewUrl::class)->handle($video->clips()->first()))
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->visible(auth()->user()->hasRole(RoleEnum::SUPER_ADMIN->value))
                    ->requiresConfirmation()
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [
            AssignmentsRelationManager::class,
            ClipsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVideos::route('/'),
            'view' => ViewVideo::route('/{record}'),
            // 'edit'   => Pages\EditVideo::route('/{record}/edit'),
        ];
    }
}
