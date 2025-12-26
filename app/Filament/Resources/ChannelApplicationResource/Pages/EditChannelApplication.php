<?php

namespace App\Filament\Resources\ChannelApplicationResource\Pages;

use App\Application\Channel\ApproveChannelApplication;
use App\Enum\Channel\ApplicationEnum;
use App\Filament\Resources\ChannelApplicationResource;
use App\Models\ChannelApplication as ChannelApplicationModel;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
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
            /**
             * @var User $user
             */
            $user = Filament::auth()->user();
            try {
                app(ApproveChannelApplication::class)->handle($record, $user);
            } catch (\Throwable $e) {
                Notification::make()
                    ->title(__('errors.channel_application.approval_failed_notification'))
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
                return;
            }
        }
    }
}
