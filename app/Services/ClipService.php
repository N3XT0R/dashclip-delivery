<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Clip;
use App\Models\User;
use App\Repository\ClipRepository;
use App\Repository\UserRepository;

class ClipService
{
    public function __construct(private ClipRepository $clipRepository)
    {
    }


    public function assignUserFromSubmittedBy(Clip $clip, User $user): bool
    {
        $clip->setAttribute('user_id', $user->getKey());
        return $clip->save();
    }


    public function assignUploaderIfPossible(Clip $clip): bool
    {
        $userRepository = app(UserRepository::class);
        $submittedBy = trim($clip->submitted_by);

        if ($submittedBy === '') {
            return false;
        }

        $user = $userRepository->getUserByDisplayName($submittedBy);

        if (!$user) {
            return false;
        }

        return $this->assignUserFromSubmittedBy($clip, $user);
    }

}