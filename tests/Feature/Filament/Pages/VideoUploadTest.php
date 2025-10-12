<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\VideoUpload;
use App\Jobs\ProcessUploadedVideo;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
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
        $disk = \Storage::fake();
        $user = User::factory()->admin()->create(['name' => 'Tester']);
        $this->actingAs($user);

        $disk->put('uploads/tmp/file1.mp4', 'a');
        $disk->put('uploads/tmp/file2.mp4', 'b');

        $path1 = $disk->path('uploads/tmp/file1.mp4');
        $path2 = $disk->path('uploads/tmp/file2.mp4');

        $file1 = new class($path1) {
            public function __construct(private string $path)
            {
            }

            public function store($dir): string
            {
                return 'uploads/tmp/'.basename($this->path);
            }

            public function getClientOriginalName(): string
            {
                return 'one.mp4';
            }

            public function getClientOriginalExtension(): string
            {
                return 'mp4';
            }
        };

        $file2 = new class($path2) {
            public function __construct(private string $path)
            {
            }

            public function store($dir): string
            {
                return 'uploads/tmp/'.basename($this->path);
            }

            public function getClientOriginalName(): string
            {
                return 'two.mov';
            }

            public function getClientOriginalExtension(): string
            {
                return 'mov';
            }
        };

        $state = [
            'clips' => [
                [
                    'file' => $file1,
                    'start_sec' => 1,
                    'end_sec' => 3,
                    'note' => 'first',
                    'bundle_key' => 'B1',
                    'role' => 'R1',
                ],
                [
                    'file' => $file2,
                    'start_sec' => 2,
                    'end_sec' => 4,
                    'note' => 'second',
                    'bundle_key' => 'B2',
                    'role' => 'R2',
                ],
            ],
        ];

        $page = new VideoUpload();
        $page->form = new class($state) {
            public function __construct(private array $state)
            {
            }

            public function getState(): array
            {
                return $this->state;
            }

            public function fill(): void
            {
            }
        };

        $page->submit();

        Bus::assertDispatchedTimes(ProcessUploadedVideo::class, 2);
        Bus::assertDispatched(ProcessUploadedVideo::class,
            static function (ProcessUploadedVideo $job) use ($user, $file1) {
                return $job->originalName === $file1->getClientOriginalName()
                    && $job->ext === $file1->getClientOriginalExtension()
                    && $job->start === 1
                    && $job->end === 3
                    && $job->note === 'first'
                    && $job->bundleKey === 'B1'
                    && $job->role === 'R1'
                    && $job->submittedBy === $user->name;
            });
        Bus::assertDispatched(ProcessUploadedVideo::class,
            static function (ProcessUploadedVideo $job) use ($user, $file2) {
                return $job->originalName === $file2->getClientOriginalName()
                    && $job->ext === $file2->getClientOriginalExtension()
                    && $job->start === 2
                    && $job->end === 4
                    && $job->note === 'second'
                    && $job->bundleKey === 'B2'
                    && $job->role === 'R2'
                    && $job->submittedBy === $user->name;
            });
    }
}
