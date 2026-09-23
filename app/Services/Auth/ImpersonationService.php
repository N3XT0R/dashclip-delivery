<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Exceptions\Auth\ImpersonationNotAllowedException;
use App\Models\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Lets an administrator look at the user area with the eyes of another user.
 *
 * The administrator stays signed in to the administration; the other user is signed in to the user
 * area on top of that, and the session remembers who started it. Actions that only record what a
 * user did, such as marking an offer as downloaded, are skipped while this is running.
 */
readonly class ImpersonationService
{
    private const string SESSION_KEY = 'impersonator_id';

    public function __construct(
        private RoleRepository $roleRepository,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Sign the administrator in to the user area as the given user.
     *
     * @param User $administrator
     * @param User $user the account to look at
     * @return void
     * @throws ImpersonationNotAllowedException
     */
    public function start(User $administrator, User $user): void
    {
        $this->guardAgainstForbiddenImpersonation($administrator, $user);

        Auth::guard(GuardEnum::STANDARD->value)->login($user);
        Session::put(self::SESSION_KEY, $administrator->getKey());

        activity('impersonation')
            ->causedBy($administrator)
            ->performedOn($user)
            ->log('impersonation started');
    }

    /**
     * End a running impersonation; the administrator keeps their own session.
     * @return void
     */
    public function stop(): void
    {
        $administrator = $this->impersonator();
        $user = Auth::guard(GuardEnum::STANDARD->value)->user();

        Auth::guard(GuardEnum::STANDARD->value)->logout();
        Session::forget(self::SESSION_KEY);

        if ($administrator instanceof User && $user instanceof User) {
            activity('impersonation')
                ->causedBy($administrator)
                ->performedOn($user)
                ->log('impersonation stopped');
        }
    }

    /**
     * Whether a view of another user is running right now.
     *
     * The marker alone is not enough: signing out of the user area by hand leaves it behind, and a
     * left-over marker must not block the next view.
     * @return bool
     */
    public function isActive(): bool
    {
        return Session::has(self::SESSION_KEY) && Auth::guard(GuardEnum::STANDARD->value)->check();
    }

    /**
     * The administrator who started the running impersonation.
     * @return User|null
     */
    public function impersonator(): ?User
    {
        $id = Session::get(self::SESSION_KEY);

        return $id === null ? null : $this->userRepository->findById((int)$id);
    }

    /**
     * Whether the administrator may look at the application as the given user.
     * @param User|null $administrator
     * @param User $user
     * @return bool
     */
    public function canImpersonate(?User $administrator, User $user): bool
    {
        if (!$administrator instanceof User) {
            return false;
        }

        try {
            $this->guardAgainstForbiddenImpersonation($administrator, $user);
        } catch (ImpersonationNotAllowedException) {
            return false;
        }

        return true;
    }

    /**
     * @throws ImpersonationNotAllowedException
     */
    private function guardAgainstForbiddenImpersonation(User $administrator, User $user): void
    {
        if ($this->isActive()) {
            throw new ImpersonationNotAllowedException('Another impersonation is already running.');
        }

        Session::forget(self::SESSION_KEY);

        if (!$this->roleRepository->canAccessEverything($administrator)) {
            throw new ImpersonationNotAllowedException('Only administrators may view the user area as someone else.');
        }

        if ($administrator->is($user)) {
            throw new ImpersonationNotAllowedException('An administrator cannot impersonate themselves.');
        }

        if ($this->roleRepository->canAccessEverything($user)) {
            throw new ImpersonationNotAllowedException('Administrators cannot be impersonated.');
        }

        if (!$user->canAccessPanel(Filament::getPanel(PanelEnum::STANDARD->value))) {
            throw new ImpersonationNotAllowedException('This account has no access to the user area.');
        }
    }
}
