<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UploadChannelLogoRequest extends FormRequest
{
    /**
     * Largest accepted logo in kilobytes, the same limit as the channel settings page.
     */
    public const int MAX_KILOBYTES = 512;

    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                'file',
                'mimetypes:image/png,image/jpeg,image/webp',
                'max:' . self::MAX_KILOBYTES,
            ],
        ];
    }
}
