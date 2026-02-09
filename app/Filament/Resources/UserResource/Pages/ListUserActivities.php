<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Filament\Resources\UserResource;

class ListUserActivities extends ListActivities
{
    protected static string $resource = UserResource::class;
}