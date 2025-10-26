<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\PathBuilderService;
use Tests\TestCase;

class PathBuilderServiceTest extends TestCase
{
    protected PathBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PathBuilderService();
    }

    public function testForVideoReturnsCorrectPath(): void
    {
        $hash = hash('sha256', 'test');
        $ext = 'mp4';
        $actualPath = $this->service->forVideo($hash, $ext);
        $this->assertEquals(
            'videos/9f/86/9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08.mp4',
            $actualPath
        );
    }

    public function testForPreviewReturnsCorrectPath(): void
    {
        $id = 123;
        $start = 0;
        $end = 10;
        $hash = md5($id.'_'.$start.'_'.$end);
        $actualPath = $this->service->forPreview($id, $start, $end);
        $this->assertEquals(
            sprintf('previews/%s.mp4', $hash),
            $actualPath
        );
    }

    public function testForPreviewByHashReturnsCorrectPath(): void
    {
        $hash = md5('custom_hash');
        $actualPath = $this->service->forPreviewByHash($hash);
        $this->assertEquals(
            'previews/1f/9d/1f9d42c2cbd56b1194faa9955b97eda3.mp4',
            $actualPath
        );
    }


    public function testForDropboxReturnsCorrectPath(): void
    {
        $basePath = '';
        $filename = 'document.pdf';
        $actualPath = $this->service->forDropbox($basePath, $filename);
        $this->assertEquals(
            '/document.pdf',
            $actualPath
        );
    }

    public function testJoinReturnsCorrectPath(): void
    {
        $part1 = 'folder';
        $part2 = 'subfolder';
        $part3 = 'file.txt';
        $actualPath = $this->service->join($part1, $part2, $part3);
        $this->assertEquals(
            '/folder/subfolder/file.txt',
            $actualPath
        );
    }
}