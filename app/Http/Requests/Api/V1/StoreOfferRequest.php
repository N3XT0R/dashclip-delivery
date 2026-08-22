<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use App\Repository\ChannelRepository;
use App\Repository\VideoRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\ValidationException;

class StoreOfferRequest extends FormRequest
{
    public function __construct(
        private readonly VideoRepository $videoRepository,
        private readonly ChannelRepository $channelRepository,
    ) {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'video_id' => ['required', 'integer'],
            'channel_id' => ['required', 'integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $unknown = array_diff(array_keys($this->json()->all()), ['video_id', 'channel_id']);
        if ($unknown !== []) {
            throw ValidationException::withMessages(
                array_fill_keys($unknown, 'This field cannot be set via the API.'),
            );
        }
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var User $user */
                $user = $this->user('api');

                if (
                    $this->filled('video_id')
                    && !$this->videoRepository->visibleForUser($user)->whereKey($this->input('video_id'))->exists()
                ) {
                    $validator->errors()->add('video_id', 'The selected video is not accessible.');
                }

                if (
                    $this->filled('channel_id')
                    && !$this->channelRepository->visibleForUser($user)->whereKey($this->input('channel_id'))->exists()
                ) {
                    $validator->errors()->add('channel_id', 'The selected channel is not accessible.');
                }
            },
        ];
    }
}
