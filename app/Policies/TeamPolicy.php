<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Repository\RoleRepository;
use App\Repository\TeamRepository;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    public function __construct(private TeamRepository $teamRepository, private RoleRepository $roleRepository)
    {
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Team');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $team): bool
    {
        return $this->teamRepository->isMemberOfTeam($user, $team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create:Team');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        return $this->teamRepository->isUserOwnerOfTeam($user, $team) ||
            $this->roleRepository->canAccessEverything($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        return $user->can('Delete:Team');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Team $team): bool
    {
        return $user->can('Restore:Team');
    }

    public function restoreAny(User $authUser): bool
    {
        return $authUser->can('RestoreAny:Team');
    }

    public function replicate(User $authUser, Team $channel): bool
    {
        return $authUser->can('Replicate:Team');
    }

    public function reorder(User $authUser): bool
    {
        return $authUser->can('Reorder:Team');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Team $team): bool
    {
        return $user->can('ForceDelete:Team');
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Team');
    }

    public function manageChannels(User $user, ?Team $team): bool
    {
        if (!$team) {
            return false;
        }

        return $this->teamRepository->isUserOwnerOfTeam($user, $team) ||
            $this->roleRepository->canAccessEverything($user);
    }
}
