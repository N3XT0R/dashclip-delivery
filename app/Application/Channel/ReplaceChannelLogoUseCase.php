<?php

declare(strict_types=1);

namespace App\Application\Channel;

use App\Exceptions\Channel\ChannelLogoProcessingException;
use App\Models\Channel;
use App\Repository\ChannelRepository;
use App\Services\Channel\ChannelLogoImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores a new channel logo, shrunk like in the channel settings, and removes the previous file.
 */
readonly class ReplaceChannelLogoUseCase
{
    public const string DISK = 'public';
    public const string DIRECTORY = 'channel-logos';

    public function __construct(
        private ChannelLogoImageService $imageService,
        private ChannelRepository $channelRepository,
    ) {
    }

    /**
     * @param Channel $channel
     * @param UploadedFile $logo validated PNG, JPEG or WebP image
     * @return Channel the updated channel
     * @throws ChannelLogoProcessingException when the image cannot be read or resized
     */
    public function handle(Channel $channel, UploadedFile $logo): Channel
    {
        $contents = $this->imageService->fitIntoBox((string)$logo->get());
        $path = self::DIRECTORY . '/' . Str::ulid()->toBase32() . '.' . $this->extensionOf($contents);

        $disk = Storage::disk(self::DISK);
        $disk->put($path, $contents, 'public');

        $previous = $channel->logo_path;
        $updated = $this->channelRepository->update($channel, ['logo_path' => $path]);

        if (is_string($previous) && $previous !== '' && $previous !== $path) {
            $disk->delete($previous);
        }

        return $updated;
    }

    private function extensionOf(string $contents): string
    {
        return match (getimagesizefromstring($contents)[2] ?? null) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_WEBP => 'webp',
            default => 'png',
        };
    }
}
