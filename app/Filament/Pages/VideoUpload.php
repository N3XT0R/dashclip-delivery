<?php

namespace App\Filament\Pages;

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
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class VideoUpload extends Page implements HasForms
{
    use InteractsWithForms, HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ArrowUpTray;
    protected static ?string $navigationLabel = 'Video Upload';
    protected static string|\UnitEnum|null $navigationGroup = 'Media';
    protected ?string $subheading = 'Diese Seite ist noch experementell';
    protected static ?string $title = 'Video Upload (alpha)';
    protected string $view = 'filament.pages.video-upload';

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
                        FileUpload::make('file')
                            ->label('Video')
                            ->required()
                            ->disk('public')
                            ->directory('uploads/tmp')
                            ->acceptedFileTypes([
                                'video/mp4',
                                'application/mp4',
                                'application/octet-stream',
                            ])
                            ->mimeTypeMap([
                                'mp4' => 'video/mp4',
                            ]),
                        Hidden::make('duration')
                            ->default(0)
                            ->required()
                            ->dehydrated(),
                        $this->timeFields(),
                        View::make('filament.forms.components.clip-selector')
                            ->dehydrated(false),
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
                            ->trim(),
                        TextInput::make('role')->label('Rolle')
                            ->datalist([
                                'F' => 'Front',
                                'R' => 'Rear',
                            ])
                            ->trim(),
                    ])
            ])
            ->statePath('data');
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
                    ->dehydrateStateUsing(fn($state) => static::toSeconds($state)),

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
                    ->dehydrateStateUsing(fn($state) => static::toSeconds($state)),
            ]);
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

        foreach ($state['clips'] ?? [] as $clip) {
            $file = $clip['file'];
            ProcessUploadedVideo::dispatch(
                user: $user,
                path: \Storage::disk('public')->path($file),
                originalName: $file->getClientOriginalName(),
                ext: $file->getClientOriginalExtension(),
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
