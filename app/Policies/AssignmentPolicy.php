<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Assignment;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssignmentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Assignment');
    }

    public function view(AuthUser $authUser, Assignment $assignment): bool
    {
        return $authUser->can('View:Assignment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Assignment');
    }

    public function update(AuthUser $authUser, Assignment $assignment): bool
    {
        return $authUser->can('Update:Assignment');
    }

    public function delete(AuthUser $authUser, Assignment $assignment): bool
    {
        return $authUser->can('Delete:Assignment');
    }

    public function restore(AuthUser $authUser, Assignment $assignment): bool
    {
        return $authUser->can('Restore:Assignment');
    }

    public function forceDelete(AuthUser $authUser, Assignment $assignment): bool
    {
        return $authUser->can('ForceDelete:Assignment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Assignment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Assignment');
    }

    public function replicate(AuthUser $authUser, Assignment $assignment): bool
    {
        return $authUser->can('Replicate:Assignment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Assignment');
    }

}