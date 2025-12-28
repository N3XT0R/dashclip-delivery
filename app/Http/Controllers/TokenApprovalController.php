<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enum\TokenPurposeEnum;
use App\Services\ActionTokenService;
use Symfony\Component\HttpFoundation\Response;

final class TokenApprovalController extends Controller
{
    public function __construct(
        private readonly ActionTokenService $actionTokenService,
    ) {
    }

    public function update(string $purpose, string $token)
    {
        $purposeEnum = TokenPurposeEnum::tryFrom($purpose);
        if (!$purposeEnum) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $actionToken = $this->actionTokenService->consume($purposeEnum, $token);

        if (!$actionToken) {
            abort(Response::HTTP_GONE);
        }

        $view = $this->resolveViewForPurpose($purposeEnum);

        if ($view && view()->exists($view)) {
            return view($view, ['token' => $actionToken, 'purpose' => $purposeEnum]);
        }

        return response()->noContent();
    }

    private function resolveViewForPurpose(TokenPurposeEnum $purpose): ?string
    {
        return match ($purpose) {
            TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL =>
            'tokens.channel-access-approved',

            TokenPurposeEnum::CHANNEL_ACTIVATION_APPROVAL =>
            'tokens.channel-activation-approved',
        };
    }
}
