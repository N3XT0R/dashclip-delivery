<?php

declare(strict_types=1);

namespace App\Application\Clips;

use App\Models\Clip;
use Illuminate\Support\Facades\Storage;

class GetPreviewUrl
{

    public function handle(Clip $clip): string
    {
        return Storage::disk($clip->preview_disk)->url($clip->preview_path);
    }
}
