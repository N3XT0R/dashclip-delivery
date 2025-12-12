<?php

namespace App\Filament\Resources\ChannelApplicationResource\Pages;

use App\Filament\Resources\ChannelApplicationResource;
use App\Models\ChannelApplication as ChannelApplicationModel;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditChannelApplication extends EditRecord
{
    protected static string $resource = ChannelApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $meta = $record->meta->toArray();
        $data['meta'] = array_replace_recursive($meta, $data['meta'] ?? []);
        return parent::handleRecordUpdate($record, $data);
    }

    public function afterSave(): void
    {
        /**
         * @var ChannelApplicationModel $record
         */
        $record = $this->getRecord();
        $hasExistingChannel = $record->channel_id !== null;
        /**
         * prepare to send notification or perform other actions based on status change
         */
    }
}
