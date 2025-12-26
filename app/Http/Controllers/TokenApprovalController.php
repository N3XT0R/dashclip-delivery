<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ActionTokenService;
use Illuminate\Http\Request;

final class TokenApprovalController extends Controller
{
    public function __construct(
        private readonly ActionTokenService $actionTokenService,
    ) {
    }

    public function update(Request $request, string $token)
    {
        $actionToken = $this->actionTokenService->consume(
            purpose: $request->get('purpose'),
            plainToken: $token
        );

        if (!$actionToken) {
            return redirect()
                ->route('token.invalid')
                ->with('error', __('This approval link is no longer valid.'));
        }

        return redirect()
            ->route('token.success')
            ->with('success', __('Your approval has been confirmed.'));
    }
}
