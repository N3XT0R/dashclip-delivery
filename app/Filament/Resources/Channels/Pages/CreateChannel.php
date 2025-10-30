<?php

namespace App\Filament\Resources\Channels\Pages;

use App\Events\ChannelCreated;
use App\Filament\Resources\Channels\ChannelResource;
use App\Models\Channel;
use Filament\Resources\Pages\CreateRecord;

class CreateChannel extends CreateRecord
{
    protected static string $resource = ChannelResource::class;

    protected function afterCreate(): void
    {
        /**
         * @var Channel $record
         */
        $record = $this->record;
        event(new ChannelCreated($record));
    }
}
