<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class FileInfoDto
{
    public function __construct(
        public string $path,
        public string $basename,
        public string $extension,
        public ?string $originalName = null,
    ) {
    }

    public static function fromPath(string $path): self
    {
        return new self(
            path: $path,
            basename: basename($path),
            extension: pathinfo($path, PATHINFO_EXTENSION),
        );
    }

    public function isCsv(): bool
    {
        return $this->isOneOfExtensions(['csv', 'txt']);
    }

    public function isOneOfExtensions(array $extensions): bool
    {
        return in_array(strtolower($this->extension), $extensions, true);
    }
}
