<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\VideoUpload;
use App\Jobs\ProcessUploadedVideo;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class VideoUploadTest extends DatabaseTestCase
{

    public function testRegularUserHasAccess(): void
    {
        $regularUser = User::factory()->standard()->create();
        $this->actingAs($regularUser);

        Livewire::test(VideoUpload::class)
            ->assertStatus(200);
    }

    /**
     * @return void
     * @todo refactor this test to not be skipped
     */
    public function testSubmitDispatchesJobForEachClip(): void
    {
        Bus::fake();
        $disk = Storage::fake('public');
        $user = User::factory()->admin()->create(['name' => 'Tester']);
        $this->actingAs($user);
        $disk->makeDirectory('uploads/tmp/');

        $disk->put('uploads/tmp/file1.mp4', 'a');

        $path1 = 'uploads/tmp/file1.mp4';

        $state = [
            'clips' => [
                [
                    'file' => $path1,
                    'original_name' => 'one.mp4',
                    'start_sec' => 1,
                    'end_sec' => 3,
                    'duration' => 3,
                    'note' => 'first',
                    'bundle_key' => 'B1',
                    'role' => 'R1',
                ]
            ],
        ];

        $page = new VideoUpload();
        $page->form = new class($state) {
            private bool $validated = false;

            public function __construct(private array $state)
            {
            }

            public function validate(): void
            {
                $this->validated = true;
                foreach ($this->state['clips'] ?? [] as $index => $clip) {
                    if (($clip['duration'] ?? 0) < 1) {
                        throw ValidationException::withMessages([
                            "clips.$index.duration" => 'The duration must be at least 1 second.',
                        ]);
                    }
                }
            }

            public function getState(): array
            {
                if (!$this->validated) {
                    throw new \RuntimeException('Form state accessed before validation.');
                }
                return $this->state;
            }

            public function fill(): void
            {
            }
        };

        $page->submit();

        Bus::assertDispatchedTimes(ProcessUploadedVideo::class, 1);
        Bus::assertDispatched(ProcessUploadedVideo::class,
            static function (ProcessUploadedVideo $job) use ($user) {
                return $job->fileInfoDto->basename === 'file1.mp4'
                    && $job->fileInfoDto->extension === 'mp4'
                    && $job->start === 1
                    && $job->end === 3
                    && $job->note === 'first'
                    && $job->bundleKey === 'B1'
                    && $job->role === 'R1'
                    && $job->submittedBy === $user->display_name;
            });
    }

    public function testSubmitRequiresDurationGreaterThanZero(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $disk = Storage::fake();
        $disk->put('uploads/tmp/file-zero.mp4', 'z');
        $path = $disk->path('uploads/tmp/file-zero.mp4');

        $file = new class($path) {
            public function __construct(private string $path)
            {
            }

            public function store($dir): string
            {
                return 'uploads/tmp/'.basename($this->path);
            }

            public function getClientOriginalName(): string
            {
                return 'zero.mp4';
            }

            public function getClientOriginalExtension(): string
            {
                return 'mp4';
            }
        };

        $state = [
            'clips' => [
                [
                    'file' => $file,
                    'start_sec' => 0,
                    'end_sec' => 1,
                    'duration' => 0,
                    'note' => null,
                    'bundle_key' => null,
                    'role' => null,
                ]
            ],
        ];

        $page = new VideoUpload();
        $page->form = new class($state) {
            private bool $validated = false;

            public function __construct(private array $state)
            {
            }

            public function validate(): void
            {
                $this->validated = true;

                foreach ($this->state['clips'] ?? [] as $index => $clip) {
                    if (($clip['duration'] ?? 0) < 1) {
                        throw ValidationException::withMessages([
                            "clips.$index.duration" => 'The duration must be at least 1 second.',
                        ]);
                    }
                }
            }

            public function getState(): array
            {
                if (!$this->validated) {
                    throw new \RuntimeException('Form state accessed before validation.');
                }

                return $this->state;
            }

            public function fill(): void
            {
            }
        };

        $this->expectException(ValidationException::class);
        $page->submit();
    }
}
