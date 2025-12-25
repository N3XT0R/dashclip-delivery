<?php

namespace App\Filament\Resources\ChannelApplicationResource\Pages;

use App\Enum\Channel\ApplicationEnum;
use App\Filament\Resources\ChannelApplicationResource;
use App\Models\ChannelApplication as ChannelApplicationModel;
use App\Services\Channel\ChannelApplicationService;
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
        $data['meta'] = array_replace_recursive($meta, $data['meta'] ?? $meta);
        return parent::handleRecordUpdate($record, $data);
    }

    public function afterSave(): void
    {
        /**
         * @var ChannelApplicationModel $record
         */
        $record = $this->getRecord();

        if (ApplicationEnum::APPROVED === $record->status) {
            $service = app(ChannelApplicationService::class);
            $result = $service->approveChannelApplication($record);
        }
    }
}
