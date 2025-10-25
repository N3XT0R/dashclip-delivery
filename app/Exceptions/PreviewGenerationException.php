<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class PreviewGenerationException extends RuntimeException
{
    public function __construct(
        public readonly array $context,
        ?Throwable $previous = null
    ) {
        $message = sprintf(
            'Preview generation failed for %s (%s): %s',
            $context['relative_path'] ?? 'unknown',
            $context['disk_path'] ?? 'unknown',
            $previous?->getMessage() ?? 'Unknown error'
        );

        parent::__construct($message, 0, $previous);
    }

    public static function fromDisk(
        string $relativePath,
        string $diskPath,
        ?Throwable $previous = null
    ): self {
        return new self([
            'relative_path' => $relativePath,
            'disk_path' => $diskPath,
            'exception' => $previous?->getMessage(),
        ], $previous);
    }
}
