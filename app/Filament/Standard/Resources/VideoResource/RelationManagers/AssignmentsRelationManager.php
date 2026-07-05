<?php

namespace App\Filament\Standard\Resources\VideoResource\RelationManagers;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';
    protected static ?string $title = 'filament.standard.video_assignments.title';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('filament.standard.video_assignments.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['channel', 'downloads']))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('channel.name')
                    ->label(__('filament.standard.video_assignments.columns.channel'))
                    ->description(__('filament.standard.video_assignments.descriptions.channel'))
                    ->icon('heroicon-m-link')
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('filament.standard.video_assignments.columns.status'))
                    ->badge()
                    ->icon(fn (string $state) => $this->statusIcon($state))
                    ->color(fn (string $state) => $this->statusColor($state))
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label(__('filament.standard.video_assignments.columns.valid_until'))
                    ->dateTime('d.m.Y H:i')
                    ->description(__('filament.standard.video_assignments.descriptions.expires_at'))
                    ->sortable(),

                TextColumn::make('download_state')
                    ->label(__('filament.standard.video_assignments.columns.download_status'))
                    ->state(fn (Assignment $record) => $this->downloadLabel($record))
                    ->icon(fn (Assignment $record) => $this->downloadIcon($record))
                    ->color(fn (Assignment $record) => $this->downloadColor($record))
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('filament.standard.video_assignments.filters.status'))
                    ->options([
                        StatusEnum::QUEUED->value => __('filament.standard.video_assignments.options.queued'),
                        StatusEnum::NOTIFIED->value => __('filament.standard.video_assignments.options.notified'),
                        StatusEnum::PICKEDUP->value => __('filament.standard.video_assignments.options.picked_up'),
                        StatusEnum::REJECTED->value => __('filament.standard.video_assignments.options.rejected'),
                        StatusEnum::EXPIRED->value => __('filament.standard.video_assignments.options.expired'),
                    ]),
            ])
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading(__('filament.standard.video_assignments.empty.heading'))
            ->emptyStateDescription(__('filament.standard.video_assignments.empty.description'));
    }

    private function downloadLabel(Assignment $assignment): string
    {
        $latestDownload = $assignment->downloads->sortByDesc('downloaded_at')->first();

        if ($assignment->status === StatusEnum::REJECTED->value) {
            return __('filament.standard.video_assignments.download.returned');
        }

        if ($assignment->status === StatusEnum::EXPIRED->value) {
            return __('filament.standard.video_assignments.download.expired');
        }

        if ($latestDownload?->downloaded_at) {
            return __('filament.standard.video_assignments.download.downloaded_at', [
                'date' => Carbon::parse($latestDownload?->downloaded_at)->isoFormat('DD.MM.YYYY HH:mm'),
            ]);
        }

        return __('filament.standard.video_assignments.download.not_downloaded');
    }

    private function downloadIcon(Assignment $assignment): string
    {
        return match (true) {
            $assignment->status === StatusEnum::REJECTED->value => 'heroicon-m-arrow-uturn-left',
            $assignment->status === StatusEnum::EXPIRED->value => 'heroicon-m-clock',
            $assignment->downloads->isNotEmpty() => 'heroicon-m-arrow-down-tray',
            default => 'heroicon-m-bell',
        };
    }

    private function downloadColor(Assignment $assignment): string
    {
        return match (true) {
            $assignment->status === StatusEnum::REJECTED->value => 'warning',
            $assignment->status === StatusEnum::EXPIRED->value => 'gray',
            $assignment->downloads->isNotEmpty() => 'success',
            default => 'primary',
        };
    }

    private function statusIcon(string $status): string
    {
        return match ($status) {
            StatusEnum::NOTIFIED->value, StatusEnum::QUEUED->value => 'heroicon-m-sparkles',
            StatusEnum::PICKEDUP->value => 'heroicon-m-check-circle',
            StatusEnum::REJECTED->value => 'heroicon-m-arrow-uturn-left',
            StatusEnum::EXPIRED->value => 'heroicon-m-clock',
            default => 'heroicon-m-information-circle',
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            StatusEnum::NOTIFIED->value, StatusEnum::QUEUED->value => 'success',
            StatusEnum::PICKEDUP->value => 'primary',
            StatusEnum::REJECTED->value => 'warning',
            StatusEnum::EXPIRED->value => 'gray',
            default => 'gray',
        };
    }
}
