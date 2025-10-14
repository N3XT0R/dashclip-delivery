<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessUploadedVideo;
use App\Models\Clip;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class VideoUpload extends Page implements HasForms
{
    use InteractsWithForms, HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ArrowUpTray;
    protected static ?string $navigationLabel = 'Video Upload';
    protected static string|\UnitEnum|null $navigationGroup = 'Media';
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
                            ->acceptedFileTypes(['video/mp4'])
                            ->storeFiles(false),
                        View::make('filament.forms.components.clip-selector')
                            ->dehydrated(false),
                        Hidden::make('duration')->default(0)->dehydrated(),
                        $this->timeFields(),
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
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            $end = $get('end_sec');
                            if ($end !== null && static::toSeconds($value) <= (int)$end) {
                                $fail('Der Startzeitpunkt muss kleiner als der Endzeitpunkt sein.');
                            }
                        };
                    })
                    ->afterStateHydrated(function (TextInput $component, $state) {
                        if (!$state) {
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
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            $start = $get('start_sec');
                            $duration = $get('duration') ?? null;
                            $endValue = static::toSeconds($value);

                            if ($start !== null && $endValue >= (int)$start) {
                                $fail('Der Endzeitpunkt muss größer als der Startzeitpunkt sein.');
                            }

                            if ($duration !== null && $endValue > (int)$duration) {
                                $fail('Das Ende darf nicht hinter der Videolänge liegen.');
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
        $state = $this->form->getState();
        $user = Auth::user()?->name;

        foreach ($state['clips'] ?? [] as $clip) {
            /** @var TemporaryUploadedFile $file */
            $file = $clip['file'];
            $stored = $file->store('uploads/tmp');

            ProcessUploadedVideo::dispatch(
                user: auth()->user(),
                path: \Storage::disk()->path($stored),
                originalName: $file->getClientOriginalName(),
                ext: $file->getClientOriginalExtension(),
                start: (int)($clip['start_sec'] ?? 0),
                end: (int)($clip['end_sec'] ?? 0),
                submittedBy: $user,
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
