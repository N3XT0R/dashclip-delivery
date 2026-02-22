<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Resources\Videos\VideoResource;
use App\Models\Clip;
use App\Models\Video;
use App\Repository\ClipRepository;
use App\Repository\TeamRepository;
use Carbon\CarbonInterval;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CreateVideo extends CreateRecord
{
    protected static string $resource = VideoResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title(__('filament.video_upload.form.messages.success.process_started'))
            ->success();
    }

    public static function getNavigationGroup(): string
    {
        return __('filament.video_upload.navigation_group');
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament.video_upload.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('filament.video_upload.subheading');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.video_upload.navigation_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        $this->getFileComponent(),
                        $this->getDurationComponent(),
                        TextEntry::make('upload_hint')
                            ->label(__('filament.video_upload.form.fields.upload_hint'))
                            ->state(__('filament.video_upload.form.fields.upload_hint_state'))
                            ->visible(fn(Get $get): bool => (int)($get('duration') ?? 0) < 1)
                            ->extraAttributes(['class' => 'text-sm text-gray-500 italic'])
                            ->columnSpanFull(),
                        $this->timeFields(),
                        $this->getClipSelectorComponent(),
                        Textarea::make('clip.note')->label('Notiz')
                            ->rows(5)
                            ->autosize()
                            ->trim(),
                        TextInput::make('clip.bundle_key')
                            ->label(__('filament.video_upload.form.fields.bundle_key'))
                            ->datalist(
                                Clip::query()
                                    ->whereNotNull('bundle_key')
                                    ->whereHas('video', fn($q) => $q->doesntHave('assignments'))
                                    ->pluck('bundle_key')
                                    ->unique()
                                    ->values()
                                    ->all()
                            )
                            ->trim()
                            ->helperText(
                                __('filament.video_upload.form.fields.bundle_key_helper_text')
                            ),
                        TextInput::make('clip.role')->label(__('filament.video_upload.form.fields.role'))
                            ->datalist([
                                'F' => 'Front',
                                'R' => 'Rear',
                            ])
                            ->helperText(
                                __('filament.video_upload.form.fields.role_helper_text')
                            )
                            ->trim()
                    ])
                    ->columnSpanFull()
            ]);
    }

    protected function getFileComponent(): FileUpload
    {
        return FileUpload::make('file')
            ->label(__('filament.video_upload.form.fields.file'))
            ->required()
            ->disk('videos')
            ->directory(auth()->id())
            ->visibility('public')
            ->storeFileNamesIn('original_name')
            ->acceptedFileTypes(['video/mp4'])
            ->multiple(false)
            ->moveFiles()
            ->acceptedFileTypes([
                'video/mp4',
                'application/mp4',
                'application/octet-stream',
                'binary/octet-stream',
            ])
            ->mimeTypeMap([
                'mp4' => 'video/mp4',
            ]);
    }

    protected function getDurationComponent(): Hidden
    {
        return Hidden::make('clip.duration')
            ->default(0)
            ->required()
            ->dehydrated()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                // Falls end_sec noch leer, automatisch übernehmen
                if (blank($get('clip.end_sec')) && (int)$state > 0) {
                    $minutes = floor($state / 60);
                    $seconds = $state % 60;
                    $set('clip.end_sec', sprintf('%02d:%02d', $minutes, $seconds));
                }
            });
    }

    protected function timeFields(): Grid
    {
        return Grid::make(2)
            ->schema([
                TextInput::make('clip.start_sec')
                    ->label(__('filament.video_upload.form.fields.start_sec'))
                    ->required()
                    ->placeholder('mm:ss')
                    ->mask('99:99')
                    ->rule(function (Get $get) {
                        return static function (string $attribute, $value, Closure $fail) use ($get) {
                            $end = $get('clip.end_sec');
                            if ($end !== null && static::toSeconds($value) >= static::toSeconds($end)) {
                                $fail(__('errors.video_upload.error.start_sec_must_be_lower'));
                            }
                        };
                    })
                    ->afterStateHydrated(function (TextInput $component, $state) {
                        if ($state === null) {
                            $state = 0;
                        }
                        $minutes = floor($state / 60);
                        $seconds = $state % 60;
                        $component->state(sprintf('%02d:%02d', $minutes, $seconds));
                    })
                    ->dehydrateStateUsing(fn($state) => static::toSeconds($state))
                    ->disabled(fn(Get $get) => (int)($get('clip.duration') ?? 0) < 1)
                    ->reactive(),

                TextInput::make('clip.end_sec')
                    ->label(__('filament.video_upload.form.fields.end_sec'))
                    ->required()
                    ->placeholder('mm:ss')
                    ->mask('99:99')
                    ->rule(function (Get $get) {
                        return static function (string $attribute, $value, Closure $fail) use ($get) {
                            $start = $get('clip.start_sec');
                            $duration = $get('clip.duration');
                            $endValue = static::toSeconds($value);

                            if ($start !== null && $endValue <= (int)$start) {
                                $fail(__('errors.video_upload.error.end_sec_must_be_greater'));
                            }

                            if ($duration !== null && $endValue > (int)$duration) {
                                $fail(
                                    __(
                                        'errors.video_upload.error.end_sec_cannot_be_greater_than_duration',
                                        [
                                            'duration' => CarbonInterval::seconds($duration)
                                                ->cascade()
                                                ->format('%I:%S'),
                                        ])
                                );
                            }
                        };
                    })
                    ->afterStateHydrated(function (TextInput $component, $state, Get $get) {
                        $duration = $get('clip.duration');
                        if (empty($state) && $duration) {
                            $state = $duration;
                        }
                        $minutes = floor($state / 60);
                        $seconds = $state % 60;
                        $component->state(sprintf('%02d:%02d', $minutes, $seconds));
                    })
                    ->dehydrateStateUsing(fn($state) => static::toSeconds($state))
                    ->disabled(fn(Get $get) => (int)($get('clip.duration') ?? 0) < 1)
                    ->reactive(),
            ]);
    }

    protected function getClipSelectorComponent(): View
    {
        return View::make('filament.forms.components.clip-selector')
            ->dehydrated(false);
    }

    protected static function toSeconds($state): int
    {
        if (preg_match('/^(\d+):(\d{1,2})$/', $state, $matches)) {
            return ((int)$matches[1] * 60) + (int)$matches[2];
        }
        return (int)$state;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $filePath = $data['file'];
        $disk = Storage::disk('videos');
        $data['path'] = $filePath;
        $data['file_size'] = $disk->size($filePath);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['team_id'] = app(TeamRepository::class)->getDefaultTeamForUser(auth()->user())?->getKey();
        $model = parent::handleRecordCreation($data);
        $data['clip']['video_id'] = $model->getKey();
        $data['clip']['user_id'] = auth()->id();
        app(ClipRepository::class)->create($data['clip']);

        return $model;
    }

    protected function afterCreate(): void
    {
        /**
         * @var Video $record
         * @note dispatch processing job after the transaction has been committed to ensure that the file is available for processing
         */
        $record = $this->record;
    }
}
