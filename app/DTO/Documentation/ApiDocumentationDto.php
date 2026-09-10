<?php

declare(strict_types=1);

namespace App\DTO\Documentation;

class ApiDocumentationDto
{
    public function __construct(
        public string $key,
        public string $title,
        public string $uiUrl,
        public string $specUrl,
    ) {
    }
}
