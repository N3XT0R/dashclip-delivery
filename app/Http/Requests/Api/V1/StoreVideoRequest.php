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
            'file' => [
                'required',
                'file',
                'mimetypes:video/mp4,video/quicktime,video/x-matroska',
                // The ~2GB limit is intentional for video delivery; sourced from the shared
                // Livewire upload rule (single source of truth) and covered by a 422 test.
                $this->resolveMaxFileSizeRule(), // NOSONAR (S5693): reviewed, limit is intentional
            ],
            'clip' => ['required', 'array'],
            'clip.start_sec' => ['required', 'integer', 'min:0'],
            'clip.end_sec' => ['required', 'integer', 'gt:clip.start_sec'],
        ];
    }

    /**
     * Resolve the upload size-limit rule from the Livewire panel-upload
     * config, keeping a single source of truth with the panel; falls back
     * to Livewire's own default when no "max:" rule is configured.
     */
    private function resolveMaxFileSizeRule(): string
    {
        /** @var array<int, string> $rules */
        $rules = config('livewire.temporary_file_upload.rules', []);

        foreach ($rules as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'max:')) {
                return $rule;
            }
        }

        return 'max:2048000';
    }
}
