<?php

namespace App\Filament\Standard\Pages;

use App\DTO\Channel\ChannelApplicationRequestDto;
use App\Enum\Channel\ApplicationEnum;
use App\Models\ChannelApplication as ChannelApplicationModel;
use App\Repository\ChannelRepository;
use App\Services\ChannelService;
use App\Support\FilamentComponents;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class ChannelApplication extends Page implements HasForms, HasTable
{

    use InteractsWithForms;
    use InteractsWithTable;
    use HasPageShield {
        canAccess as canAccessShield;
    }

    protected string $view = 'filament.standard.pages.channel-application';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencil;

    protected static ?string $title = 'filament.channel_application.title';
    protected static ?string $navigationLabel = 'filament.channel_application.navigation_label';
    protected static string|UnitEnum|null $navigationGroup = 'filament.channel_application.navigation_group';

    public ?array $data = [
        'channel_id' => null,
        'note' => null,
        'other_channel_request' => false,
        'new_channel_name' => null,
        'new_channel_creator_name' => null,
        'new_channel_email' => null,
        'new_channel_youtube_name' => null,
    ];
    public ?ChannelApplicationModel $pendingApplication = null;

    protected static function getChannelRepository(): ChannelRepository
    {
        return app(ChannelRepository::class);
    }

    public static function canAccess(): bool
    {
        $canAccess = self::canAccessShield();
        if ($canAccess) {
            $canAccess = static::getChannelRepository()->getChannelApplicationsByUser(
                auth()->user(),
                ApplicationEnum::APPROVED)
                ->isEmpty();
        }

        return $canAccess;
    }

    public function mount(): void
    {
        $this->pendingApplication = static::getChannelRepository()->getChannelApplicationsByUser(
            auth()->user(),
            ApplicationEnum::PENDING
        )->first();
        $this->data['new_channel_email'] = auth()->user()->email;
    }

    public function getTitle(): string|Htmlable
    {
        return __(static::$title);
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function getNavigationGroup(): string
    {
        return __(static::$navigationGroup);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Checkbox::make('other_channel_request')
                    ->label(__('filament.channel_application.form.request_other_channel'))
                    ->reactive(),

                Select::make('channel_id')
                    ->label(__('filament.channel_application.form.channel_label'))
                    ->options(fn() => app(ChannelRepository::class)
                        ->getActiveChannels()->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->placeholder(__('filament.channel_application.form.choose_channel'))
                    ->hidden(fn($get) => $get('other_channel_request'))
                    ->requiredIfDeclined(fn($get) => !$get('other_channel_request')),
                Section::make(__('filament.channel_application.form.new_channel_section_label'))
                    ->visible(fn($get) => $get('other_channel_request'))
                    ->schema([
                        TextInput::make('new_channel_name')
                            ->label(__('filament.channel_application.form.new_channel_name_label'))
                            ->requiredIfAccepted(fn($get) => $get('other_channel_request')),
                        TextInput::make('new_channel_creator_name')
                            ->label(__('filament.channel_application.form.new_channel_creator_name_label'))
                            ->requiredIfAccepted(fn($get) => $get('other_channel_request')),
                        TextInput::make('new_channel_email')
                            ->label(__('filament.channel_application.form.new_channel_email_label'))
                            ->email()
                            ->requiredIfAccepted(fn($get) => $get('other_channel_request')),
                        TextInput::make('new_channel_youtube_name')
                            ->prefixIcon(Heroicon::OutlinedAtSymbol)
                            ->label(__('filament.channel_application.form.new_channel_youtube_name_label'))
                            ->required(false),
                    ]),
                MarkdownEditor::make('note')
                    ->label(__('filament.channel_application.form.note_label'))
                    ->disableToolbarButtons([
                        'attachFiles',
                    ])
                    ->maxLength(500)
                    ->placeholder(__('filament.channel_application.form.note_placeholder')),
                FilamentComponents::tosCheckbox(),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return ChannelApplicationModel::query()->where('user_id', auth()->id());
            })
            ->heading(__('filament.channel_application.table.record_title'))
            ->columns([
                TextColumn::make('channel')
                    ->label(__('filament.channel_application.table.columns.channel'))
                    ->getStateUsing(function (ChannelApplicationModel $record) {
                        return $record->meta->channel['name']
                            ?? $record->channel?->name
                            ?? __('filament.channel_application.table.columns.channel_unknown');
                    }),
                TextColumn::make('status')
                    ->label(__('filament.channel_application.table.columns.status'))
                    ->formatStateUsing(function ($state) {
                        return __('filament.channel_application.status.'.strtolower($state));
                    }),
                TextColumn::make('created_at')
                    ->label(__('filament.channel_application.table.columns.submitted_at'))
                    ->dateTime(),
                TextColumn::make('updated_at')
                    ->label(__('filament.channel_application.table.columns.updated_at'))
                    ->dateTime(),
            ])
            ->recordActions([
                ViewAction::make('view')
                    ->modal()
                    ->modalHeading(__('filament.channel_application.table.actions.view.modal_heading'))
                    ->label(__('filament.channel_application.table.actions.view.label'))
                    ->schema([
                        MarkdownEditor::make('note')
                            ->label(__('filament.channel_application.form.note_label'))
                            ->translateLabel()
                            ->disabled(),
                        MarkdownEditor::make('meta.reject_reason')
                            ->label(__('filament.channel_application.form.reject_reason_label'))
                            ->translateLabel()
                            ->disabled()
                            ->visible(fn($record) => $record->status === ApplicationEnum::REJECTED->value),
                    ])
                    ->icon(Heroicon::OutlinedEye),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public function submit(): void
    {
        $state = $this->form->getState();
        $dto = new ChannelApplicationRequestDto(
            channelId: $state['channel_id'] ?? null,
            note: $state['note'] ?? '',
            otherChannelRequest: $state['other_channel_request'] ?? false,
            newChannelName: $state['new_channel_name'] ?? null,
            newChannelCreatorName: $state['new_channel_creator_name'] ?? null,
            newChannelEmail: $state['new_channel_email'] ?? null,
            newChannelYoutubeName: $state['new_channel_youtube_name'] ?? null,
        );

        try {
            app(ChannelService::class)->applyForAccess($dto, auth()->user());
            Notification::make()
                ->title(__('filament.channel_application.messages.success.application_submitted'))
                ->success()
                ->send();
            $this->form->fill([]);
            $this->mount();
        } catch (\DomainException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
