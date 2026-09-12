<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class PublicDocumentService
{
    /**
     * Render the maintained release history with its existing document heading.
     */
    public function changelog(): string
    {
        return Str::markdown(File::get(base_path('CHANGELOG.md')), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Return the original license text for escaped, whitespace-preserving display.
     */
    public function license(): string
    {
        return File::get(base_path('LICENSE'));
    }
}
