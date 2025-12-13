<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Widgets\AvailableOffersStatsWidget;
use App\Filament\Standard\Widgets\DownloadedOffersStatsWidget;
use App\Filament\Standard\Widgets\ExpiredOffersStatsWidget;
use App\Services\Queries\AssignmentQueryInterface;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use BackedEnum;
use UnitEnum;

class MyOffers extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.standard.pages.my-offers';

    protected AssignmentQueryInterface $query;

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
        // Placeholder table to keep the page renderable while dedicated tabbed tables are wired.
        return $table->query($this->query->available())->emptyStateHeading(__('filament.my_offers.empty_state'));
    }

    protected function getAuthenticatedUser(): ?Authenticatable
    {
        return auth()->user();
    }
}
