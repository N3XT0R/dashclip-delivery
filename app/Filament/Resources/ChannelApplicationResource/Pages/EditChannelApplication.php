<?php

namespace App\Filament\Resources\ChannelApplicationResource\Pages;

use App\Filament\Resources\ChannelApplicationResource;
use App\Models\ChannelApplication as ChannelApplicationModel;
use Filament\Resources\Pages\EditRecord;

class EditChannelApplication extends EditRecord
{
    protected static string $resource = ChannelApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    public function afterSave(): void
    {
        /**
         * @var ChannelApplicationModel $record
         */
        $record = $this->getRecord();
        /**
         * prepare to send notification or perform other actions based on status change
         */
    }
}
