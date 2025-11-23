<?php

namespace App\Filament\Standard\Resources\VideoResource\RelationManagers;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';
    protected static ?string $title = 'Assignments';


    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['channel', 'downloads']))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('channel.name')
                    ->label('Channel')
                    ->description('Ziel-Channel für dieses Angebot')
                    ->icon('heroicon-m-link')
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn(string $state) => $this->statusIcon($state))
                    ->color(fn(string $state) => $this->statusColor($state))
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Gültig bis')
                    ->dateTime('d.m.Y H:i')
                    ->description('Offer-Link läuft ab')
                    ->sortable(),

                TextColumn::make('download_state')
                    ->label('Download-Status')
                    ->state(fn(Assignment $record) => $this->downloadLabel($record))
                    ->icon(fn(Assignment $record) => $this->downloadIcon($record))
                    ->color(fn(Assignment $record) => $this->downloadColor($record))
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status filtern')
                    ->options([
                        StatusEnum::QUEUED->value => 'Offen',
                        StatusEnum::NOTIFIED->value => 'Verfügbar',
                        StatusEnum::PICKEDUP->value => 'Angenommen',
                        StatusEnum::REJECTED->value => 'Zurückgegeben',
                        StatusEnum::EXPIRED->value => 'Abgelaufen',
                    ]),
            ])
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('Keine Assignments vorhanden')
            ->emptyStateDescription('Sobald dieses Video verteilt wird, siehst du hier alle Offers.');
    }

    private function downloadLabel(Assignment $assignment): string
    {
        $latestDownload = $assignment->downloads->sortByDesc('downloaded_at')->first();

        if ($assignment->status === StatusEnum::REJECTED->value) {
            return 'Zurückgegeben';
        }

        if ($assignment->status === StatusEnum::EXPIRED->value) {
            return 'Abgelaufen';
        }

        if ($latestDownload?->downloaded_at) {
            return 'Heruntergeladen am '.Carbon::parse($latestDownload?->downloaded_at)->isoFormat('DD.MM.YYYY HH:mm');
        }

        return 'Noch nicht heruntergeladen';
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
