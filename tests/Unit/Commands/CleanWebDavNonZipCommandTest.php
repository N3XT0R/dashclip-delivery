<?php
declare(strict_types=1);

namespace Tests\Unit\Commands;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CleanWebDavNonZipCommandTest extends TestCase
{
    public function testDeletesNonZipFilesFromWebDavDirectories(): void
    {
        Storage::fake('import');
        Storage::disk('import')->put('webdav/1/video.mp4', 'content');
        Storage::disk('import')->put('webdav/1/archive.zip', 'content');
        Storage::disk('import')->put('webdav/2/photo.jpg', 'content');

        $this->artisan('clean:webdav-non-zip')->assertExitCode(0);

        Storage::disk('import')->assertMissing('webdav/1/video.mp4');
        Storage::disk('import')->assertExists('webdav/1/archive.zip');
        Storage::disk('import')->assertMissing('webdav/2/photo.jpg');
    }

    public function testKeepsZipFilesIntact(): void
    {
        Storage::fake('import');
        Storage::disk('import')->put('webdav/1/archive.zip', 'content');
        Storage::disk('import')->put('webdav/1/other.ZIP', 'content');

        $this->artisan('clean:webdav-non-zip')->assertExitCode(0);

        Storage::disk('import')->assertExists('webdav/1/archive.zip');
        Storage::disk('import')->assertExists('webdav/1/other.ZIP');
    }
}
