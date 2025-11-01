<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Upload;

use App\Services\Dropbox\AutoRefreshTokenProvider;
use App\Services\Upload\DropboxUploadService;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Spatie\Dropbox\Client;
use Spatie\Dropbox\UploadSessionCursor;
use Tests\DatabaseTestCase;

final class DropboxUploadServiceTest extends DatabaseTestCase
{
    public function testUploadFileWithSmallFileUsesDirectUpload(): void
    {
        Storage::fake('tmp');
        Storage::disk('tmp')->put('foo.txt', 'bar');

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('upload')
            ->once()
            ->withArgs(fn($path, $content) => str_contains($path, 'foo.txt') && $content === 'bar'
            )
            ->andReturn(['mocked' => true]);

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
        $bigContent = str_repeat('A', 20 * 1024 * 1024); // 20 MB -> 2x Append + Finish
        Storage::disk('tmp')->put('big.bin', $bigContent);

        $cursor = new UploadSessionCursor('session123', 0);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('uploadSessionStart')
            ->once()
            ->andReturn($cursor);
        $client->shouldReceive('uploadSessionAppend')
            ->atLeast()->once()
            ->andReturnUsing(function ($chunk, $cur) {
                $cur->offset += strlen($chunk);
                return $cur;
            });
        $client->shouldReceive('uploadSessionFinish')
            ->once()
            ->andReturn(['finished' => true]);

        $service = new DropboxUploadService(
            Mockery::mock(AutoRefreshTokenProvider::class),
            $client
        );

        $service->uploadFile(Storage::disk('tmp'), 'big.bin', 'big.bin');

        $this->addToAssertionCount(1);
    }
}
