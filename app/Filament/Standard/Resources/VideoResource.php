<?php

namespace App\Filament\Standard\Resources;

use App\Enum\StatusEnum;
use App\Filament\Standard\Resources\VideoResource\Pages;
use App\Filament\Standard\Resources\VideoResource\RelationManagers\AssignmentsRelationManager;
use App\Models\Video;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static ?string $modelLabel = 'Video';
    protected static ?string $pluralModelLabel = 'Videos';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;
    protected static ?string $recordTitleAttribute = 'original_name';


    public static function getNavigationGroup(): string
    {
        return __('nav.media');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make()
                    ->columns([
                        'md' => 2,
                    ])
                    ->schema([
                        ViewField::make('video_preview')
                            ->label('filament.video_resource.view.fields.video_preview')
                            ->translateLabel()
                            ->view('filament.forms.components.video-preview')
                            ->columnSpan(1)
                            ->extraAttributes(['class' => 'rounded-lg shadow-md w-full max-w-xs']),
                        Grid::make()
                            ->schema([
                                TextEntry::make('original_name')
                                    ->label('filament.video_resource.view.fields.original_name')
                                    ->translateLabel(),
                                TextEntry::make('duration')
                                    ->label('filament.video_resource.view.fields.duration')
                                    ->translateLabel()
                                    ->state(function (Video $record) {
                                        return self::formatDuration(
                                            $record->clips()?->first()?->getAttribute('duration')
                                        );
                                    }),
                                TextEntry::make('processing_status')
                                    ->label('filament.video_resource.view.fields.processing_status')
                                    ->translateLabel()
                                    ->badge()
                                    ->formatStateUsing(function (Video $record) {
                                        if ($record->processing_status === null) {
                                            return __('status.processing_status.unknown');
                                        }
                                        return __('status.processing_status.'.$record->processing_status->value);
                                    }),
                                TextEntry::make('bundle_key')
                                    ->label('filament.video_resource.view.fields.bundle_key')
                                    ->translateLabel()
                                    ->state(function (Video $record) {
                                        return $record->clips()?->first()?->getAttribute('bundle_key');
                                    })
                                    ->extraAttributes(['class' => 'text-lg font-semibold']),
                                TextEntry::make('created_at')
                                    ->label('filament.video_resource.view.fields.created_at')
                                    ->translateLabel()
                                    ->dateTime('d.m.Y, H:i'),
                                TextEntry::make('status_label')
                                    ->label('filament.video_resource.view.fields.status')
                                    ->translateLabel()
                                    ->badge()
                                    ->state(fn(Video $record) => self::determineStatusLabel($record))
                                    ->color(fn(Video $record) => self::statusColor(self::determineStatusLabel($record)))
                                    ->icon(fn(Video $record) => self::statusIcon(self::determineStatusLabel($record))),
                                TextEntry::make('available_assignments_count')
                                    ->label('filament.video_resource.view.fields.available_assignments_count')
                                    ->translateLabel(),
                                TextEntry::make('expired_assignments_count')
                                    ->label('filament.video_resource.view.fields.expired_assignments_count')
                                    ->translateLabel(),
                            ])
                            ->columns(2),
                    ]),
                Grid::make()
                    ->schema([
                        TextEntry::make('assignmentWithNote.note')
                            ->label('filament.video_resource.view.fields.note')
                            ->visible(fn(Video $record) => !empty($record->assignmentWithNote?->note))
                            ->translateLabel(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn(Video $record) => static::getUrl('view', ['record' => $record]))
            ->columns([
                ViewColumn::make('video_preview')
                    ->label(__('filament.video_resource.view.fields.preview'))
                    ->view('filament.forms.components.video-preview'),
                TextColumn::make('original_name')
                    ->label(__('filament.video_resource.view.fields.original_name'))
                    ->description('Meine hochgeladenen Clips')
                    ->sortable()
                    ->searchable()
                    ->limit(60),
                TextColumn::make('processing_status')
                    ->label('filament.video_resource.view.fields.processing_status')
                    ->translateLabel()
                    ->badge()
                    ->formatStateUsing(function (Video $record) {
                        if ($record->processing_status === null) {
                            return __('status.processing_status.unknown');
                        }
                        return __('status.processing_status.'.$record->processing_status->value);
                    }),
                TextColumn::make('bundle')
                    ->label(__('filament.video_resource.view.fields.bundle_key'))
                    ->sortable()
                    ->searchable()
                    ->getStateUsing(function (Video $record) {
                        return $record->clips()?->first()?->getAttribute('bundle_key');
                    })
                    ->limit(60),
                TextColumn::make('role')
                    ->label(__('filament.video_resource.view.fields.view_type'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable()
                    ->getStateUsing(fn(?Video $record) => $record->clips()?->first()?->getAttribute('role')),
                TextColumn::make('duration')
                    ->label(__('filament.video_resource.view.fields.duration'))
                    ->state(fn(Video $record
                    ) => self::formatDuration($record->clips()?->first()?->getAttribute('duration')))
                    ->tooltip('Gesamtlänge des Videos')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label((__('filament.video_resource.view.fields.created_at')))
                    ->dateTime('d.m.Y, H:i')
                    ->sortable(),

                TextColumn::make('status_label')
                    ->label(__('filament.video_resource.view.fields.status'))
                    ->badge()
                    ->state(fn(Video $record) => self::determineStatusLabel($record))
                    ->color(fn(Video $record) => self::statusColor(self::determineStatusLabel($record)))
                    ->icon(fn(Video $record) => self::statusIcon(self::determineStatusLabel($record)))
                    ->sortable(query: fn(
                        Builder $query,
                        string $direction
                    ) => $query->orderBy('available_assignments_count', $direction)),

                TextColumn::make('available_assignments_count')
                    ->label(__('filament.video_resource.view.fields.available_assignments_count'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('expired_assignments_count')
                    ->label(__('filament.video_resource.view.fields.expired_assignments_count'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('assignment_state')
                    ->label('Offer-Status')
                    ->options(__('status.assignment_state'))
                    ->default('all')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? 'all') {
                            'active' => $query->whereHas('assignments', function (Builder $assignmentQuery) {
                                $assignmentQuery
                                    ->whereIn('status', StatusEnum::getReadyStatus())
                                    ->where(function (Builder $expiryQuery) {
                                        $expiryQuery
                                            ->whereNull('expires_at')
                                            ->orWhere('expires_at', '>', now());
                                    });
                            }),
                            'downloaded' => $query->whereHas(
                                'assignments',
                                fn(Builder $assignmentQuery) => $assignmentQuery->where(
                                    'status',
                                    StatusEnum::PICKEDUP->value
                                )
                            ),
                            'expired' => $query->whereHas(
                                'assignments',
                                fn(Builder $assignmentQuery) => $assignmentQuery->where(
                                    'status',
                                    StatusEnum::EXPIRED->value
                                )
                            ),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make('view-details')
                    ->defaultColor('gray')
                    ->label(__('filament.video_resource.view.fields.view_details'))
                    ->icon('heroicon-m-eye')
                    ->button(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading(__('filament.video_resource.view.messages.no_videos'))
            ->emptyStateDescription(__('filament.video_resource.view.messages.table_description'));
    }

    public static function getRelations(): array
    {
        return [
            AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'view' => Pages\ViewVideo::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('clips', function (Builder $query) {
                $query->where('clips.user_id', Filament::auth()->id());
            })
            ->withCount([
                'assignments as available_assignments_count' => function (Builder $query) {
                    $query
                        ->whereIn('status', StatusEnum::getReadyStatus())
                        ->where(function (Builder $expiryQuery) {
                            $expiryQuery
                                ->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        });
                },
                'assignments as expired_assignments_count' => fn(Builder $query) => $query->where(
                    'status',
                    StatusEnum::EXPIRED->value
                ),
                'assignments as downloaded_assignments_count' => fn(Builder $query) => $query->where(
                    'status',
                    StatusEnum::PICKEDUP->value
                ),
                'assignments as assignments_count',
            ]);
    }

    private static function formatDuration(?int $duration): string
    {
        if (!$duration || $duration <= 0) {
            return '–';
        }

        $minutes = intdiv($duration, 60);
        $seconds = $duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    private static function determineStatusLabel(Video $video): string
    {
        $available = (int)$video->getAttribute('available_assignments_count');
        $expired = (int)$video->getAttribute('expired_assignments_count');
        $total = (int)$video->getAttribute('assignments_count');
        $downloaded = (int)$video->getAttribute('downloaded_assignments_count');

        return match (true) {
            $downloaded > 0 => 'Heruntergeladen',
            $available > 0 => 'Verfügbar',
            $expired > 0 => 'Abgelaufen',
            $total > 0 && $available === 0 => 'Alle verteilt',
            default => 'In Vorbereitung',
        };
    }

    private static function statusColor(string $label): string
    {
        return match ($label) {
            'Verfügbar' => 'success',
            'Alle verteilt' => 'primary',
            'Abgelaufen' => 'gray',
            default => 'warning',
        };
    }

    private static function statusIcon(string $label): string
    {
        return match ($label) {
            'Verfügbar' => 'heroicon-m-sparkles',
            'Alle verteilt' => 'heroicon-m-check-badge',
            'Abgelaufen' => 'heroicon-m-clock',
            default => 'heroicon-m-arrow-path',
        };
    }
}
