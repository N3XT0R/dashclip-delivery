<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActionToken;
use App\Repository\ActionTokenRepository;
use App\Services\ActionTokenService;

final class TokenApprovalController extends Controller
{
    public function __construct(
        private ActionTokenService $actionTokenService,
        private ActionTokenRepository $actionTokenRepository
    ) {
    }

    public function update(ActionToken $actionToken)
    {
    }
}
