<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Channel;

use App\Exceptions\Channel\ChannelLogoProcessingException;
use App\Services\Channel\ChannelLogoImageService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class ChannelLogoImageServiceTest extends TestCase
{
    private ChannelLogoImageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ChannelLogoImageService::class);
    }

    public function testWideImageIsShrunkIntoTheBoxKeepingItsAspectRatio(): void
    {
        $contents = $this->service->fitIntoBox($this->imageContents('png', 1000, 250));

        self::assertSame([256, 64, IMAGETYPE_PNG], $this->dimensionsAndType($contents));
    }

    public function testTallImageIsShrunkIntoTheBox(): void
    {
        $contents = $this->service->fitIntoBox($this->imageContents('jpg', 300, 600));

        self::assertSame([128, 256, IMAGETYPE_JPEG], $this->dimensionsAndType($contents));
    }

    public function testWebpKeepsItsFormat(): void
    {
        if ((imagetypes() & IMG_WEBP) === 0) {
            self::markTestSkipped('The image library of this PHP build has no WebP support.');
        }

        $contents = $this->service->fitIntoBox($this->imageContents('webp', 512, 512));

        self::assertSame([256, 256, IMAGETYPE_WEBP], $this->dimensionsAndType($contents));
    }

    public function testSmallImageIsLeftUntouched(): void
    {
        $original = $this->imageContents('png', 120, 80);

        self::assertSame($original, $this->service->fitIntoBox($original));
    }

    public function testUnreadableImageDataThrows(): void
    {
        $this->expectException(ChannelLogoProcessingException::class);

        $this->service->fitIntoBox('definitely not an image');
    }

    private function imageContents(string $extension, int $width, int $height): string
    {
        $file = UploadedFile::fake()->image('logo.' . $extension, $width, $height);

        return (string)file_get_contents($file->getRealPath());
    }

    /**
     * @param string $contents
     * @return array{0: int, 1: int, 2: int}
     */
    private function dimensionsAndType(string $contents): array
    {
        $info = getimagesizefromstring($contents);

        return [$info[0], $info[1], $info[2]];
    }
}
