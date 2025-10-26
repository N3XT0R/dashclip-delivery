<?php

declare(strict_types=1);

namespace Services;

use App\DTO\FileInfoDto;
use App\Services\DynamicStorageService;
use Tests\TestCase;

class DynamicStorageServiceTest extends TestCase
{
    protected DynamicStorageService $dynamicStorageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dynamicStorageService = $this->app->make(DynamicStorageService::class);
    }

    public function testFromPathReturnsFilesystem(): void
    {
        $inboxPath = base_path('tests/Fixtures/Inbox');
        $disk = $this->dynamicStorageService->fromPath($inboxPath);
        $this->assertNotNull($disk);
        $this->assertTrue($disk->exists('example.txt'));
    }


    public function testFromPathThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        $invalidPath = base_path('tests/Fixtures/NonExistentDirectory/xxx.txt');
        $this->dynamicStorageService->fromPath($invalidPath);
    }

    public function testListFilesReturnsFileInfoDtos(): void
    {
        $inboxPath = base_path('tests/Fixtures/Inbox');
        $disk = $this->dynamicStorageService->fromPath($inboxPath);
        $fileInfoDtos = $this->dynamicStorageService->listFiles($disk);
        $this->assertNotNull($disk);
        $this->assertNotCount(0, $fileInfoDtos);

        foreach ($fileInfoDtos as $fileInfoDto) {
            $this->assertInstanceOf(FileInfoDto::class, $fileInfoDto);
            $this->assertNotEmpty($fileInfoDto->path);
            $this->assertNotEmpty($fileInfoDto->basename);
            $this->assertNotEmpty($fileInfoDto->extension);
        }
    }

    public function testGetHashForFileInfoDtoReturnsHash(): void
    {
        $inboxPath = base_path('tests/Fixtures/Inbox');
        $disk = $this->dynamicStorageService->fromPath($inboxPath);
        $fileInfoDtos = $this->dynamicStorageService->listFiles($disk);
        self::assertNotNull($disk);
        self::assertNotCount(0, $fileInfoDtos);

        $textFileDto = $fileInfoDtos->filter(fn(FileInfoDto $dto) => $dto->isOneOfExtensions(['txt']))->first();
        self::assertInstanceOf(FileInfoDto::class, $textFileDto);

        $hash = $this->dynamicStorageService->getHashForFileInfoDto($disk, $textFileDto);
        self::assertNotEmpty($hash);
        self::assertSame('73b248344dcf25034d70136a54a9200cee05df4c109100264fa4b4bc3b7b4cf4', $hash);
    }


    public function testGetHashForFilePathReturnsHash(): void
    {
        $inboxPath = base_path('tests/Fixtures/Inbox');
        $disk = $this->dynamicStorageService->fromPath($inboxPath);

        $hash = $this->dynamicStorageService->getHashForFilePath($disk, 'example.txt');
        self::assertNotEmpty($hash);
        self::assertSame('73b248344dcf25034d70136a54a9200cee05df4c109100264fa4b4bc3b7b4cf4', $hash);
    }

    public function testGetHashForFileThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        $inboxPath = base_path('tests/Fixtures/Inbox');
        $disk = $this->dynamicStorageService->fromPath($inboxPath);

        $this->dynamicStorageService->getHashForFilePath($disk, 'non_existent_file.txt');
    }
}