<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Pages;

use App\Facades\Cfg;
use App\Facades\DynamicStorage;
use App\Filament\Pages\VideoUpload;
use App\Jobs\ProcessUploadedVideo;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\DatabaseTestCase;
use Tests\Testing\Traits\CopyDiskTrait;

final class VideoUploadTest extends DatabaseTestCase
{
    use CopyDiskTrait;

    public function testSubmitDispatchesJobForEachClip(): void
    {
        Bus::fake();
        $inboxPath = base_path('tests/Fixtures/Inbox/Videos/');
        $dynamicStorage = DynamicStorage::fromPath($inboxPath);
        $disk = Storage::fake('uploads');
        $this->copyDisk($dynamicStorage, $disk);
        $user = User::factory()->admin()->create(['name' => 'Tester']);
        $this->actingAs($user);
        $disk->makeDirectory('tmp/');

        $disk->put('tmp/file1.mp4', $dynamicStorage->readStream('standalone.mp4'));
        $path1 = 'tmp/file1.mp4';

        $state = [
            'file' => $path1,
            'original_name' => 'one.mp4',
            'start_sec' => 1,
            'end_sec' => 3,
            'duration' => 3,
            'note' => 'first',
            'bundle_key' => 'B1',
            'role' => 'R1',
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

                if (($this->state['duration'] ?? 0) < 1) {
                    throw ValidationException::withMessages([
                        "duration" => 'The duration must be at least 1 second.',
                    ]);
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
        Cfg::set('default_file_system', 'local', 'default');
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $disk = Storage::fake('uploads');
        $disk->put('tmp/file-zero.mp4', 'z');
        $path = $disk->path('tmp/file-zero.mp4');

        $file = new class($path) {
            public function __construct(private string $path)
            {
            }

            public function store($dir): string
            {
                return 'tmp/'.basename($this->path);
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
            'file' => $file,
            'start_sec' => 0,
            'end_sec' => 1,
            'duration' => 0,
            'note' => null,
            'bundle_key' => null,
            'role' => null,
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

                if (($this->state['duration'] ?? 0) < 1) {
                    throw ValidationException::withMessages([
                        "duration" => 'The duration must be at least 1 second.',
                    ]);
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
