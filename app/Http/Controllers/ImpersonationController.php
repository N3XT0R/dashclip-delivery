<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enum\PanelEnum;
use App\Exceptions\Auth\ImpersonationNotAllowedException;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Starts and stops looking at the user area as another user.
 */
final class ImpersonationController extends Controller
{
    public function __construct(private readonly ImpersonationService $impersonation)
    {
    }

    public function start(User $user): RedirectResponse
    {
        $administrator = auth()->user();

        try {
            $this->impersonation->start($administrator, $user);
        } catch (ImpersonationNotAllowedException $exception) {
            abort(Response::HTTP_FORBIDDEN, $exception->getMessage());
        }

        return redirect(Filament::getPanel(PanelEnum::STANDARD->value)->getUrl());
    }

    public function stop(): RedirectResponse
    {
        $this->impersonation->stop();

        return redirect(Filament::getPanel(PanelEnum::ADMIN->value)->getUrl());
    }
}
