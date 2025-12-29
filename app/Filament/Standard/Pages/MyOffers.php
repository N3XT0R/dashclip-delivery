<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages;

use App\Application\Offer\DispatchZipDownload;
use App\Enum\StatusEnum;
use App\Filament\Standard\Pages\MyOffers\Table\AssignmentTable;
use App\Filament\Standard\Pages\MyOffers\Tabs\AssignmentTabs;
use App\Filament\Standard\Widgets\ChannelWidgets\AvailableOffersStatsWidget;
use App\Filament\Standard\Widgets\ChannelWidgets\DownloadedOffersStatsWidget;
use App\Filament\Standard\Widgets\ChannelWidgets\ExpiredOffersStatsWidget;
use App\Models\Assignment;
use App\Services\LinkService;
use BackedEnum;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MyOffers extends AbstractChannelOwnerPage implements HasTable
{
    use InteractsWithTable;
    use InteractsWithActions;
    use HasTabs;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'nav.channel_owner';

    protected static ?int $navigationSort = 10;

    public ?string $note = null;


    public static function getNavigationLabel(): string
    {
        return __('my_offers.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __(static::$navigationGroup);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return trans('common.channel') . ': ' . $this->getCurrentChannel()?->name;
    }

    public function getTitle(): string
    {
        return __('my_offers.title');
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->can('page.channels.access') ?? false;
    }

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components($this->mergeComponentsIfChannelExists([
            $this->getTabsContentComponent(),
            EmbeddedTable::make(),
        ]));
    }

    protected function mergeComponentsIfChannelExists(array $components): array
    {
        $channel = $this->getCurrentChannel();

        if ($channel) {
            $zipPostUrl = app(LinkService::class)->getZipSelectedUrlForChannel($channel, now()->addDay());

            $formElement = ViewField::make('zip_form_anchor')
                ->view('filament.standard.components.zip-form-anchor', [
                    'zipPostUrl' => $zipPostUrl,
                ]);
            array_unshift($components, $formElement);
        }

        return $components;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AvailableOffersStatsWidget::class,
            DownloadedOffersStatsWidget::class,
            ExpiredOffersStatsWidget::class,
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'channelId' => $this->getCurrentChannel()?->getKey(),
        ];
    }

    public function getTabs(): array
    {
        $channel = $this->getCurrentChannel();
        return app(AssignmentTabs::class)->make($channel);
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'available';
    }

    public function table(Table $table): Table
    {
        $channel = $this->getCurrentChannel();

        if (
            Filament::auth()->user()?->cannot('page.channels.access_for_channel', $channel)
        ) {
            $channel = null;
        }

        $table = app(AssignmentTable::class)->make($table, $this, $channel);
        $table->modifyQueryUsing(fn(Builder $query): Builder => $this->modifyQueryWithActiveTab($query));

        return $table;
    }

    public function getDetailsInfolist(Assignment $assignment): Schema
    {
        return Schema::make()
            ->livewire($this)
            ->state([
                'video' => $assignment->video,
                'clips' => $assignment->video->clips,
                'note' => $assignment->note,
            ])
            ->schema([
                Section::make(__('my_offers.modal.preview.heading'))
                    ->schema([
                        ViewField::make('preview')
                            ->view('filament.standard.components.video-preview')
                            ->viewData([
                                'video' => $assignment->video,
                            ]),
                    ])
                    ->collapsible(),

                Section::make(__('my_offers.modal.metadata.heading'))
                    ->schema([
                        TextEntry::make('video.human_readable_size')
                            ->label(__('my_offers.modal.metadata.file_size'))
                            ->default('—'),
                        TextEntry::make('video.original_name')
                            ->label(__('my_offers.modal.metadata.filename'))
                            ->default('—'),
                    ])
                    ->columns(3),
                Section::make(__('my_offers.modal.note.heading'))
                    ->schema([
                        Textarea::make('note')
                            ->label(__('my_offers.modal.note.label'))
                            ->placeholder(__('my_offers.modal.note.placeholder'))
                            ->helperText(__('my_offers.modal.note.helper_text'))
                            ->disabled(function () use ($assignment): bool {
                                return false === StatusEnum::isEditableStatus($assignment->status);
                            })
                            ->rows(5),
                    ])
                    ->collapsible(),
                Section::make(__('my_offers.modal.clips.heading'))
                    ->schema([
                        ViewField::make('clips')
                            ->view('filament.standard.components.clips-table')
                            ->viewData([
                                'clips' => $assignment->video->clips()->orderBy('start_sec')->get(),
                            ]),
                    ])
                    ->collapsible()
                    ->hidden(fn(): bool => $assignment->video->clips->isEmpty()),
            ]);
    }

    public function dispatchZipDownload(iterable $ids): void
    {
        app(DispatchZipDownload::class)->handle($this, $ids);
        $this->resetTable();
    }
}
