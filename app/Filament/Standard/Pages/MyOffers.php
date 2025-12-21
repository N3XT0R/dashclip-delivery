<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Enum\StatusEnum;
use App\Filament\Standard\Widgets\AvailableOffersStatsWidget;
use App\Filament\Standard\Widgets\DownloadedOffersStatsWidget;
use App\Filament\Standard\Widgets\ExpiredOffersStatsWidget;
use App\Services\Queries\AssignmentQueryInterface;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use BackedEnum;
use App\Models\Assignment;
use UnitEnum;

class MyOffers extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.standard.pages.my-offers';

    protected AssignmentQueryInterface $query;

    protected string $activeTab = 'available';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static string|UnitEnum|null $navigationGroup = 'nav.media';

    protected static ?string $title = 'filament.my_offers.title';

    public function mount(AssignmentQueryInterface $query): void
    {
        $this->query = $query;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->hasRole(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
    }

    public function getTitle(): string|Htmlable
    {
        return __(static::$title);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AvailableOffersStatsWidget::class,
            DownloadedOffersStatsWidget::class,
            ExpiredOffersStatsWidget::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => $this->resolveTableQuery())
            ->columns($this->getTableColumns())
            ->recordUrl(null)
            ->emptyStateHeading(__('filament.my_offers.empty_state'));
    }

    public function getTabs(): array
    {
        return [
            'available' => Tab::make(__('filament.my_offers.tabs.available')),
            'downloaded' => Tab::make(__('filament.my_offers.tabs.downloaded')),
            'expired' => Tab::make(__('filament.my_offers.tabs.expired')),
            'returned' => Tab::make(__('filament.my_offers.tabs.returned')),
        ];
    }

    protected function resolveTableQuery(): Builder
    {
        $query = match ($this->activeTab) {
            'downloaded' => $this->query->downloaded(),
            'expired' => $this->query->expired(),
            'returned' => $this->query->returned(),
            default => $this->query->available(),
        };

        return $query
            ->with(['video', 'channel', 'downloads'])
            ->latest('updated_at');
    }

    /**
     * @return array<int, TextColumn>
     */
    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('video.title')
                ->label(__('Video'))
                ->wrap()
                ->limit(40)
                ->searchable(),
            TextColumn::make('channel.name')
                ->label(__('Channel'))
                ->wrap()
                ->limit(30),
            TextColumn::make('status')
                ->badge()
                ->icon(fn(string $state): string => $this->statusIcon($state))
                ->color(fn(string $state): string => $this->statusColor($state)),
            TextColumn::make('expires_at')
                ->label(__('Gültig bis'))
                ->dateTime('d.m.Y H:i')
                ->visible(fn(): bool => in_array($this->activeTab, ['available', 'expired'], true)),
            TextColumn::make('download_state')
                ->label(__('Download'))
                ->state(fn(Assignment $record): string => $this->downloadLabel($record))
                ->icon(fn(Assignment $record): string => $this->downloadIcon($record))
                ->color(fn(Assignment $record): string => $this->downloadColor($record)),
        ];
    }

    protected function getAuthenticatedUser(): ?Authenticatable
    {
        return auth()->user();
    }

    private function downloadLabel(Assignment $assignment): string
    {
        $latestDownload = $assignment->downloads->sortByDesc('downloaded_at')->first();

        if ($assignment->status === StatusEnum::REJECTED->value) {
            return 'Zurückgewiesen';
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
