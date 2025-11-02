<?php

namespace App\Filament\Pages;

use App\DTO\FileInfoDto;
use App\Facades\Cfg;
use App\Jobs\ProcessUploadedVideo;
use App\Models\Clip;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\CarbonInterval;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VideoUpload extends Page implements HasForms
{
    use InteractsWithForms, HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ArrowUpTray;
    protected static ?string $navigationLabel = 'Video Upload';
    protected static string|\UnitEnum|null $navigationGroup = 'Media';
    protected ?string $subheading = 'Diese Seite ist noch experementell';
    protected static ?string $title = 'Video Upload (alpha)';
    protected string $view = 'filament.pages.video-upload';

    protected const string SOURCE_DISK = 'local';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('clips')
                    ->label('Video')
                    ->addActionLabel('Weiteres Video hinzufügen')
                    ->defaultItems(1)
                    ->schema([
                        $this->getFileComponent(),
                        $this->getDurationComponent(),
                        TextEntry::make('upload_hint')
                            ->label('Upload-Hinweis')
                            ->state('Die Zeitfelder werden automatisch freigeschaltet, sobald ein Video hochgeladen wurde.')
                            ->visible(fn(Get $get): bool => (int)($get('duration') ?? 0) < 1)
                            ->extraAttributes(['class' => 'text-sm text-gray-500 italic'])
                            ->columnSpanFull(),
                        $this->timeFields(),
                        $this->getClipSelectorComponent(),
                        Textarea::make('note')->label('Notiz')
                            ->rows(5)
                            ->autosize()
                            ->trim(),
                        TextInput::make('bundle_key')
                            ->label('Bundle ID')
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
                            ->helperText('Optional: Verwende denselben Bundle-Key für mehrere Uploads, damit diese Videos als zusammengehörige Gruppe behandelt werden.'),
                        TextInput::make('role')->label('Rolle')
                            ->datalist([
                                'F' => 'Front',
                                'R' => 'Rear',
                            ])
                            ->helperText('Optional: Gibt die Kameraposition oder Perspektive des Videos an, z. B. Front (F) oder Rear (R).')
                            ->trim(),
                    ])
            ])
            ->statePath('data');
    }

    protected function getFileComponent(): FileUpload
    {
        return FileUpload::make('file')
            ->label('Video')
            ->required()
            ->disk(self::SOURCE_DISK)
            ->directory('uploads/tmp')
            ->acceptedFileTypes([
                'video/mp4',
                'application/mp4',
                'application/octet-stream',
                'binary/octet-stream',
            ])
            ->storeFileNamesIn('original_name')
            ->mimeTypeMap([
                'mp4' => 'video/mp4',
            ]);
    }

    protected function getDurationComponent(): Hidden
    {
        return Hidden::make('duration')
            ->default(0)
            ->required()
            ->dehydrated()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                // Falls end_sec noch leer, automatisch übernehmen
                if (blank($get('end_sec')) && (int)$state > 0) {
                    $minutes = floor($state / 60);
                    $seconds = $state % 60;
                    $set('end_sec', sprintf('%02d:%02d', $minutes, $seconds));
                }
            });
    }

    protected function timeFields(): Grid
    {
        return Grid::make(2)
            ->schema([
                TextInput::make('start_sec')
                    ->label('Start (mm:ss)')
                    ->required()
                    ->placeholder('mm:ss')
                    ->mask('99:99')
                    ->rule(function (Get $get) {
                        return static function (string $attribute, $value, Closure $fail) use ($get) {
                            $end = $get('end_sec');
                            if ($end !== null && static::toSeconds($value) >= static::toSeconds($end)) {
                                $fail('Der Startzeitpunkt muss kleiner als der Endzeitpunkt sein.');
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
                    ->disabled(fn(Get $get) => (int)($get('duration') ?? 0) < 1) // << hier
                    ->reactive(),

                TextInput::make('end_sec')
                    ->label('Ende (mm:ss)')
                    ->required()
                    ->placeholder('mm:ss')
                    ->mask('99:99')
                    ->rule(function (Get $get) {
                        return static function (string $attribute, $value, Closure $fail) use ($get) {
                            $start = $get('start_sec');
                            $duration = $get('duration') ?? null;
                            $endValue = static::toSeconds($value);

                            if ($start !== null && $endValue <= (int)$start) {
                                $fail('Der Endzeitpunkt muss größer als der Startzeitpunkt sein.');
                            }

                            if ($duration !== null && $endValue > (int)$duration) {
                                $fail(sprintf('Das Ende darf nicht hinter der Videolänge von %s liegen.',
                                    CarbonInterval::seconds($duration)->cascade()->format('%I:%S')));
                            }
                        };
                    })
                    ->afterStateHydrated(function (TextInput $component, $state, Get $get) {
                        $duration = $get('duration');
                        if (empty($state) && $duration) {
                            $state = $duration;
                        }
                        $minutes = floor($state / 60);
                        $seconds = $state % 60;
                        $component->state(sprintf('%02d:%02d', $minutes, $seconds));
                    })
                    ->dehydrateStateUsing(fn($state) => static::toSeconds($state))
                    ->disabled(fn(Get $get) => (int)($get('duration') ?? 0) < 1) // << hier
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

    public function submit(): void
    {
        $this->form->validate();
        $state = $this->form->getState();
        $user = Auth::user();
        $targetDisk = Cfg::get('default_file_system', 'default', 'dropbox');

        foreach ($state['clips'] ?? [] as $clip) {
            $file = $clip['file'] ?? '';
            $fileInfoDto = new FileInfoDto(
                $file,
                Str::afterLast($file, '/'),
                Str::afterLast($file, '.'),
                $clip['original_name'] ?? null,
            );

            ProcessUploadedVideo::dispatch(
                user: $user,
                fileInfoDto: $fileInfoDto,
                targetDisk: $targetDisk,
                sourceDisk: self::SOURCE_DISK,
                start: (int)($clip['start_sec'] ?? 0),
                end: (int)($clip['end_sec'] ?? 0),
                submittedBy: $user?->display_name,
                note: $clip['note'] ?? null,
                bundleKey: $clip['bundle_key'] ?? null,
                role: $clip['role'] ?? null,
            );
        }

        Notification::make()
            ->title('Videos werden verarbeitet')
            ->success()
            ->send();

        $this->form->fill();
    }
}
