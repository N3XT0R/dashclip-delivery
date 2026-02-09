<?php

declare(strict_types=1);

namespace App\Filament\Clusters\ActivityWorkspace\Pages;

use App\Filament\Clusters\ActivityWorkspace;
use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Filament\Resources\UserResource;

class ListUserActivities extends ListActivities
{
    protected static string $resource = UserResource::class;

    protected static ?string $cluster = ActivityWorkspace::class;
}