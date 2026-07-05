<?php

namespace App\Filament\Admin\Resources\Assignments;

use App\Enum\StatusEnum;
use App\Filament\Admin\Resources\Assignments\Pages\ListAssignments;
use App\Filament\Admin\Resources\Assignments\Pages\ViewAssignment;
use App\Filament\Admin\Resources\Videos\VideoResource;
use App\Models\Assignment;
use App\Services\LinkService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'filament.admin.navigation.media';
    protected static ?string $modelLabel = 'filament.admin.labels.assignment';
    protected static ?string $pluralModelLabel = 'filament.admin.labels.assignments';
    protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return __('filament.admin.labels.assignment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.admin.labels.assignments');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable(),

                TextColumn::make('channel.name')
                    ->label(__('filament.admin.labels.channel'))
                    ->sortable()
                    ->searchable(),

                // Show related video name if you have it; fallback to ID if not.
                TextColumn::make('video.original_name')
                    ->label(__('filament.admin.labels.video'))
                    ->toggleable()
                    ->limit(40)
                    ->url(function (Assignment $assignment) {
                        $video = $assignment->video;
                        return $video ? VideoResource::getUrl('view', ['record' => $video]) : null;
                    })
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => $state === StatusEnum::PICKEDUP->value,
                        'warning' => fn ($state) => $state === StatusEnum::QUEUED->value,
                        'info' => fn ($state) => $state === StatusEnum::NOTIFIED->value,
                    ])
                    ->sortable()
                    ->searchable(),

                TextColumn::make('attempts')
                    ->label(__('filament.admin.labels.attempts'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('last_notified_at')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('filament.admin.labels.created'))
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                // Distinct status filter
                SelectFilter::make('status')
                    ->options(fn () => Assignment::query()
                        ->whereNotNull('status')
                        ->distinct()
                        ->pluck('status', 'status')
                        ->toArray()),

                // Date range by created_at
                Filter::make('created_range')
                    ->schema([
                        DatePicker::make('from')->label(__('filament.admin.labels.from')),
                        DatePicker::make('until')->label(__('filament.admin.labels.to')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),

                // Quick "expired" filter
                Filter::make('expired')
                    ->label(__('filament.admin.labels.expired'))
                    ->query(fn ($q) => $q->whereNotNull('expires_at')->where('expires_at', '<', now())),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('offer')
                    ->label(__('filament.admin.labels.open_offer'))
                    ->url(fn (Assignment $assignment): ?string => (
                        $assignment->batch && $assignment->channel
                    )
                        ? app(LinkService::class)->getOfferUrl(
                            $assignment->batch,
                            $assignment->channel,
                            Carbon::now()->addDay()
                        )
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        // No nested relations on the Assignment resource by default
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssignments::route('/'),
            'view' => ViewAssignment::route('/{record}'),
        ];
    }
}
