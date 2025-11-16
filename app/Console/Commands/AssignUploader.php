<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repository\ClipRepository;
use App\Repository\UserRepository;
use Illuminate\Console\Command;

class AssignUploader extends Command
{
    protected $signature = 'assign:uploader';

    public function handle(ClipRepository $clipRepository, UserRepository $userRepository): int
    {
        $clipsWithoutUserId = $clipRepository->getClipsWhereUserIdIsNull();
        foreach ($clipsWithoutUserId as $clip) {
            $submittedBy = trim($clip->getAttribute('submitted_by'));
            $user = $userRepository->getUserByDisplayName($submittedBy);
            if ($user) {
                $clip->setAttribute('user_id', $user->getKey());
                $clip->save();
            }
        }

        return self::SUCCESS;
    }
}