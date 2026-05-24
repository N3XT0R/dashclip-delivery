<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Upload;

use App\Services\Dropbox\AutoRefreshTokenProvider;
use App\Services\Upload\DropboxUploadService;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Spatie\Dropbox\Client;
use Spatie\Dropbox\UploadSessionCursor;
use Tests\TestCase;

final class DropboxUploadServiceTest extends TestCase
{
    public function testUploadFileWithSmallFileUsesSessionUpload(): void
    {
        Storage::fake('tmp');
        Storage::disk('tmp')->put('foo.txt', 'bar');

        $cursor = new UploadSessionCursor('session123', 3);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('uploadSessionStart')
            ->once()
            ->andReturnUsing(function ($chunk) use ($cursor) {
                $chunk->getContents();
                return $cursor;
            });
        $client->shouldReceive('uploadSessionAppend')->never();
        $client->shouldReceive('uploadSessionFinish')
            ->once()
            ->withArgs(fn($content) => $content === '')
            ->andReturn(['finished' => true]);

        $service = new DropboxUploadService(
            Mockery::mock(AutoRefreshTokenProvider::class),
            $client
        );

        $service->uploadFile(Storage::disk('tmp'), 'foo.txt', 'foo.txt');

        $this->addToAssertionCount(1);
    }

    public function testUploadFileWithChunkedUploadSplitsAndFinishes(): void
    {
        Storage::fake('tmp');
        Storage::disk('tmp')->put('big.bin', str_repeat('A', 20 * 1024 * 1024)); // 20 MB → Start + 2× Append + Finish

        $cursor = new UploadSessionCursor('session123', 0);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('uploadSessionStart')
            ->once()
            ->andReturnUsing(function ($chunk) use ($cursor) {
                $chunk->getContents();
                return $cursor;
            });
        $client->shouldReceive('uploadSessionAppend')
            ->twice()
            ->andReturnUsing(function ($chunk, $cur) {
                $chunk->getContents();
                return $cur;
            });
        $client->shouldReceive('uploadSessionFinish')
            ->once()
            ->withArgs(fn($content) => $content === '')
            ->andReturn(['finished' => true]);

        $service = new DropboxUploadService(
            Mockery::mock(AutoRefreshTokenProvider::class),
            $client
        );

        $service->uploadFile(Storage::disk('tmp'), 'big.bin', 'big.bin');

        $this->addToAssertionCount(1);
    }
}
