<?php

namespace App\Filament\Resources\Notifications\Pages;

use App\Filament\Resources\Notifications\NotificationResource;
use Filament\Resources\Pages\ListRecords;

/**
 * @extends ListRecords<NotificationResource>
 * @deprecated will be removed in next major release
 * @note replaced by mail notifications
 */
class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;
}
