<?php

declare(strict_types=1);

namespace App\DTO;

final class FileInfoDto
{
    public function __construct(
        public readonly string $path,
        public readonly string $basename,
        public readonly string $extension,
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
}
