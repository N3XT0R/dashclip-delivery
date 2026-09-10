<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use App\Repository\AssignmentRepository;
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
        private readonly AssignmentRepository $assignmentRepository,
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

                $videoVisible = false;
                if ($this->filled('video_id')) {
                    $videoVisible = $this->videoRepository->visibleForUser($user)
                        ->whereKey($this->input('video_id'))->exists();
                    if (!$videoVisible) {
                        $validator->errors()->add('video_id', 'The selected video is not accessible.');
                    }
                }

                $channelVisible = false;
                if ($this->filled('channel_id')) {
                    $channelVisible = $this->channelRepository->visibleForUser($user)
                        ->whereKey($this->input('channel_id'))->exists();
                    if (!$channelVisible) {
                        $validator->errors()->add('channel_id', 'The selected channel is not accessible.');
                    }
                }

                if (
                    $videoVisible
                    && $channelVisible
                    && $this->assignmentRepository->existsForVideoAndChannel(
                        (int)$this->input('video_id'),
                        (int)$this->input('channel_id'),
                    )
                ) {
                    $validator->errors()->add(
                        'video_id',
                        'An offer for this video and channel already exists.',
                    );
                }
            },
        ];
    }
}
