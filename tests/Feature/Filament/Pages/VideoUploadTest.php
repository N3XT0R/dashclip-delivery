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

    public function testSubmitDispatchesJobForEachClip(): void
    {
        Bus::fake();
        $disk = Storage::fake('public');
        $user = User::factory()->admin()->create(['name' => 'Tester']);
        $this->actingAs($user);

        $disk->put('uploads/tmp/file1.mp4', 'a');
        $disk->put('uploads/tmp/file2.mov', 'b');

        $path1 = 'uploads/tmp/file1.mp4';
        $path2 = 'uploads/tmp/file2.mov';

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
                ],
                [
                    'file' => $path2,
                    'original_name' => 'two.mov',
                    'start_sec' => 2,
                    'end_sec' => 4,
                    'duration' => 4,
                    'note' => 'second',
                    'bundle_key' => 'B2',
                    'role' => 'R2',
                ],
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

        Bus::assertDispatchedTimes(ProcessUploadedVideo::class, 2);
        Bus::assertDispatched(ProcessUploadedVideo::class,
            static function (ProcessUploadedVideo $job) use ($user, $path1) {
                return $job->originalName === 'one.mp4'
                    && $job->ext === 'mp4'
                    && $job->start === 1
                    && $job->end === 3
                    && $job->note === 'first'
                    && $job->bundleKey === 'B1'
                    && $job->role === 'R1'
                    && $job->submittedBy === $user->display_name
                    && str_ends_with($job->path, $path1);
            });
        Bus::assertDispatched(ProcessUploadedVideo::class,
            static function (ProcessUploadedVideo $job) use ($user, $path2) {
                return $job->originalName === 'two.mov'
                    && $job->ext === 'mov'
                    && $job->start === 2
                    && $job->end === 4
                    && $job->note === 'second'
                    && $job->bundleKey === 'B2'
                    && $job->role === 'R2'
                    && $job->submittedBy === $user->display_name
                    && str_ends_with($job->path, $path2);
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
