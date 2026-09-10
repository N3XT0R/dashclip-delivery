<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateChannelRequest extends FormRequest
{
    public function rules(): array
    {
        return ['is_video_reception_paused' => ['required', 'boolean']];
    }

    protected function prepareForValidation(): void
    {
        $unknown = array_diff(array_keys($this->all()), ['is_video_reception_paused']);
        if ($unknown !== []) {
            throw ValidationException::withMessages(
                array_fill_keys($unknown, 'This field cannot be updated via the API.'),
            );
        }
    }
}
