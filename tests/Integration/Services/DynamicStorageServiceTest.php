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

}