<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages;

use App\Filament\Standard\Resources\VideoResource;
use App\Models\Download;
use App\Repository\DownloadRepository;
use Auth;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class DownloadHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'nav.media';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('download_history.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __(static::$navigationGroup);
    }

    public function getTitle(): string
    {
        return __('download_history.title');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => app(DownloadRepository::class)->forUser(Auth::user()))
            ->defaultSort('downloaded_at', 'desc')
            ->columns([
                TextColumn::make('assignment.video.original_name')
                    ->label(__('download_history.table.columns.video'))
                    ->url(fn (Download $record) => $this->videoUrl($record))
                    ->limit(60),
                TextColumn::make('assignment.channel.name')
                    ->label(__('download_history.table.columns.channel')),
                TextColumn::make('downloaded_at')
                    ->label(__('download_history.table.columns.downloaded_at'))
                    ->since()
                    ->dateTimeTooltip('d.m.Y, H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('view-video')
                    ->label(__('download_history.table.actions.view_video'))
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->button()
                    ->url(fn (Download $record) => $this->videoUrl($record)),
            ])
            ->toolbarActions([])
            ->emptyStateHeading(__('download_history.table.empty_state.heading'))
            ->emptyStateDescription(__('download_history.table.empty_state.description'));
    }

    private function videoUrl(Download $record): string
    {
        return VideoResource::getUrl('view', ['record' => $record->assignment->video]);
    }
}
