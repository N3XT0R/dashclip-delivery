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
}