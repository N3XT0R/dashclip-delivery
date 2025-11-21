<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OfferLinkClick;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfferLinkClickPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OfferLinkClick');
    }

    public function view(AuthUser $authUser, OfferLinkClick $offerLinkClick): bool
    {
        return $authUser->can('View:OfferLinkClick');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OfferLinkClick');
    }

    public function update(AuthUser $authUser, OfferLinkClick $offerLinkClick): bool
    {
        return $authUser->can('Update:OfferLinkClick');
    }

    public function delete(AuthUser $authUser, OfferLinkClick $offerLinkClick): bool
    {
        return $authUser->can('Delete:OfferLinkClick');
    }

    public function restore(AuthUser $authUser, OfferLinkClick $offerLinkClick): bool
    {
        return $authUser->can('Restore:OfferLinkClick');
    }

    public function forceDelete(AuthUser $authUser, OfferLinkClick $offerLinkClick): bool
    {
        return $authUser->can('ForceDelete:OfferLinkClick');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OfferLinkClick');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OfferLinkClick');
    }

    public function replicate(AuthUser $authUser, OfferLinkClick $offerLinkClick): bool
    {
        return $authUser->can('Replicate:OfferLinkClick');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OfferLinkClick');
    }

}