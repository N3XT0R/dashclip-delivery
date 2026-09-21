<?php

declare(strict_types=1);

namespace App\Services\Channel;

use App\Exceptions\Channel\ChannelLogoProcessingException;
use GdImage;

/**
 * Shrinks channel logos into the square box the channel settings use, keeping format and aspect ratio.
 */
readonly class ChannelLogoImageService
{
    public const int MAX_EDGE = 256;

    /**
     * Fit an image into a MAX_EDGE x MAX_EDGE box. Images that already fit are returned untouched.
     *
     * @param string $contents binary PNG, JPEG or WebP data
     * @return string binary image data in the original format
     * @throws ChannelLogoProcessingException when the data is no readable PNG, JPEG or WebP image
     */
    public function fitIntoBox(string $contents): string
    {
        $info = @getimagesizefromstring($contents);
        if ($info === false || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
            throw new ChannelLogoProcessingException('The logo is no readable PNG, JPEG or WebP image.');
        }

        [$width, $height, $type] = $info;
        if ($width <= self::MAX_EDGE && $height <= self::MAX_EDGE) {
            return $contents;
        }

        if (!$this->canResize($type)) {
            throw new ChannelLogoProcessingException('Logos in this format cannot be resized on this server.');
        }

        $source = @imagecreatefromstring($contents);
        if (!$source instanceof GdImage) {
            throw new ChannelLogoProcessingException('The logo image could not be decoded.');
        }

        $scale = self::MAX_EDGE / max($width, $height);
        $target = $this->createCanvas(
            max(1, (int)round($width * $scale)),
            max(1, (int)round($height * $scale)),
        );
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);

        return $this->encode($target, $type);
    }

    private function canResize(int $type): bool
    {
        if (!function_exists('imagecreatefromstring')) {
            return false;
        }

        return (imagetypes() & match ($type) {
            IMAGETYPE_PNG => IMG_PNG,
            IMAGETYPE_JPEG => IMG_JPG,
            default => IMG_WEBP,
        }) !== 0;
    }

    private function createCanvas(int $width, int $height): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        return $canvas;
    }

    /**
     * @throws ChannelLogoProcessingException
     */
    private function encode(GdImage $image, int $type): string
    {
        ob_start();
        $written = match ($type) {
            IMAGETYPE_PNG => imagepng($image),
            IMAGETYPE_JPEG => imagejpeg($image, null, 90),
            default => imagewebp($image, null, 90),
        };
        $contents = (string)ob_get_clean();

        if (!$written || $contents === '') {
            throw new ChannelLogoProcessingException('The resized logo could not be encoded.');
        }

        return $contents;
    }
}
