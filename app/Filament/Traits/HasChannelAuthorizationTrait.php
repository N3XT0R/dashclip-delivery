<?php

declare(strict_types=1);

namespace App\Filament\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasChannelAuthorizationTrait
{
    use ChannelOwnerContextTrait;

    public static function canEdit(Model $record): bool
    {
        return static::userHasAccessToChannel($record);
    }

    public static function canView(Model $record): bool
    {
        return static::userHasAccessToChannel($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::userHasAccessToChannel($record);
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::userHasAccessToChannel($record);
    }

    public static function canRestore(Model $record): bool
    {
        return static::userHasAccessToChannel($record);
    }

    public static function canReplicate(Model $record): bool
    {
        return false;
    }
}
