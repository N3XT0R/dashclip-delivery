<?php

declare(strict_types=1);

namespace App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\RelationManagers;

use App\Application\Channel\Application\RevokeChannelAccess;
use App\Models\Channel;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'channelUsers';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('channel-workspace.channel_resource.relation_manager.users.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('channel-workspace.channel_resource.relation_manager.users.columns.channel'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('channel-workspace.channel_resource.relation_manager.users.columns.email'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('pivot.is_user_verified')
                    ->label(__('channel-workspace.channel_resource.relation_manager.users.columns.verified'))
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('revokeAccess')
                    ->visible(fn (User $record): bool => auth()->user()->getKey() !== $record->getKey())
                    ->label(__('filament.user_revoke_channel_access.label'))
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        /**
                         * @var Channel $ownerRecord
                         */
                        $ownerRecord = $this->getOwnerRecord();
                        app(RevokeChannelAccess::class)->handle(
                            $record,
                            $ownerRecord,
                            auth()->user()
                        );
                    })
                    ->successNotificationTitle(__('filament.user_revoke_channel_access.success_notification_title')),
            ]);
    }
}
