<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-matroska'],
            'clip' => ['required', 'array'],
            'clip.start_sec' => ['required', 'integer', 'min:0'],
            'clip.end_sec' => ['required', 'integer', 'gt:clip.start_sec'],
        ];
    }
}
