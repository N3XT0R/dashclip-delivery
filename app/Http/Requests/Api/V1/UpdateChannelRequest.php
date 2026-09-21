<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Application\Channel\UpdateChannelSettingsUseCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateChannelRequest extends FormRequest
{
    public function rules(): array
    {
        $channelId = (int)$this->route('channel');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('channels', 'name')->ignore($channelId),
            ],
            'creator_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('channels', 'email')->ignore($channelId),
            ],
            'youtube_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_video_reception_paused' => ['sometimes', 'required', 'boolean'],
            'show_on_homepage' => ['sometimes', 'required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $fields = array_keys($this->all());

        $unknown = array_diff($fields, UpdateChannelSettingsUseCase::EDITABLE_FIELDS);
        if ($unknown !== []) {
            throw ValidationException::withMessages(
                array_fill_keys($unknown, 'This field cannot be updated via the API.'),
            );
        }

        if ($fields === []) {
            throw ValidationException::withMessages([
                'body' => 'Send at least one of: ' . implode(', ', UpdateChannelSettingsUseCase::EDITABLE_FIELDS) . '.',
            ]);
        }
    }
}
