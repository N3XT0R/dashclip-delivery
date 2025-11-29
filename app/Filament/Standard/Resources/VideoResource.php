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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static ?string $modelLabel = 'Video';
    protected static ?string $pluralModelLabel = 'Videos';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;
    protected static string|\UnitEnum|null $navigationGroup = 'Media';
    protected static ?string $recordTitleAttribute = 'original_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
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
                            ->label('Preview')
                            ->view('filament.forms.components.video-preview')
                            ->columnSpan(1)
                            ->extraAttributes(['class' => 'rounded-lg shadow-md w-full max-w-xs']),
                        Grid::make()
                            ->schema([
                                TextEntry::make('original_name')
                                    ->label('Video-Titel')
                                    ->extraAttributes(['class' => 'text-lg font-semibold']),
                                TextEntry::make('duration')
                                    ->label('Dauer')
                                    ->state(function (Video $record) {
                                        return self::formatDuration($record->clips()?->first()?->getAttribute('duration'));
                                    }),
                                TextEntry::make('bundle_key')
                                    ->label('Bundle')
                                    ->state(function (Video $record) {
                                        return $record->clips()?->first()?->getAttribute('bundle_key');
                                    })
                                    ->extraAttributes(['class' => 'text-lg font-semibold']),
                                TextEntry::make('created_at')
                                    ->label('Upload am')
                                    ->dateTime('d.m.Y, H:i'),
                                TextEntry::make('status_label')
                                    ->label('Status')
                                    ->badge()
                                    ->state(fn(Video $record) => self::determineStatusLabel($record))
                                    ->color(fn(Video $record) => self::statusColor(self::determineStatusLabel($record)))
                                    ->icon(fn(Video $record) => self::statusIcon(self::determineStatusLabel($record))),
                                TextEntry::make('available_assignments_count')
                                    ->label('Verfügbare Offers'),
                                TextEntry::make('expired_assignments_count')
                                    ->label('Abgelaufene Offers'),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn(Video $record) => static::getUrl('view', ['record' => $record]))
            ->columns([
                ImageColumn::make('preview_url')
                    ->label('Vorschau')
                    ->imageSize(48)
                    ->circular()
                    ->visible(fn(?Video $record) => filled($record?->getAttribute('preview_url')))
                    ->getStateUsing(fn(?Video $record) => (string)$record?->getAttribute('preview_url')),

                TextColumn::make('original_name')
                    ->label('Video-Titel')
                    ->description('Meine hochgeladenen Clips')
                    ->sortable()
                    ->searchable()
                    ->limit(60),
                TextColumn::make('bundle')
                    ->label('Bundle')
                    ->sortable()
                    ->searchable()
                    ->getStateUsing(function (Video $record) {
                        return $record->clips()?->first()?->getAttribute('bundle_key');
                    })
                    ->limit(60),
                TextColumn::make('role')
                    ->label('Ansicht')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable()
                    ->getStateUsing(fn(?Video $record) => $record->clips()?->first()?->getAttribute('role')),
                TextColumn::make('duration')
                    ->label('Dauer')
                    ->state(fn(Video $record
                    ) => self::formatDuration($record->clips()?->first()?->getAttribute('duration')))
                    ->tooltip('Gesamtlänge des Videos')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Upload am')
                    ->dateTime('d.m.Y, H:i')
                    ->sortable(),

                TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->state(fn(Video $record) => self::determineStatusLabel($record))
                    ->color(fn(Video $record) => self::statusColor(self::determineStatusLabel($record)))
                    ->icon(fn(Video $record) => self::statusIcon(self::determineStatusLabel($record)))
                    ->sortable(query: fn(
                        Builder $query,
                        string $direction
                    ) => $query->orderBy('available_assignments_count', $direction)),

                TextColumn::make('available_assignments_count')
                    ->label('Verfügbare Offers')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('expired_assignments_count')
                    ->label('Abgelaufene Offers')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('assignment_state')
                    ->label('Offer-Status')
                    ->options([
                        'downloaded' => 'Heruntergeladene Offers',
                        'active' => 'Nur aktive Offers',
                        'expired' => 'Abgelaufene Offers',
                        'all' => 'Alle',
                    ])
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
                            'downloaded' => $query->whereHas('assignments',
                                fn(Builder $assignmentQuery) => $assignmentQuery->where('status',
                                    StatusEnum::PICKEDUP->value)),
                            'expired' => $query->whereHas('assignments',
                                fn(Builder $assignmentQuery) => $assignmentQuery->where('status',
                                    StatusEnum::EXPIRED->value)),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make('view-details')
                    ->defaultColor('gray')
                    ->label('Details ansehen')
                    ->icon('heroicon-m-eye')
                    ->button(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Keine Videos gefunden')
            ->emptyStateDescription('Hier siehst du alle Videos, die deinem Account gehören.');
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
                'assignments as expired_assignments_count' => fn(Builder $query) => $query->where('status',
                    StatusEnum::EXPIRED->value),
                'assignments as downloaded_assignments_count' => fn(Builder $query) => $query->where('status',
                    StatusEnum::PICKEDUP->value),
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
