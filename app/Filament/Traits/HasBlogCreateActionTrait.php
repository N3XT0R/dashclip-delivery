<?php

namespace App\Filament\Traits;

use Filament\Actions\CreateAction;

trait HasBlogCreateActionTrait
{
    /**
     * Offer the resource-authorized create action consistently on editorial lists.
     * @return list<CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
