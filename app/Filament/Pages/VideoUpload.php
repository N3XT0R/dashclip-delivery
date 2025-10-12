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
                        Hidden::make('start_sec')->default(0),
                        Hidden::make('end_sec')->default(0),
                    ])
            ])
            ->statePath('data');
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
