<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Application\Channel\Application\RevokeChannelAccess;
use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Channel;
use App\Repository\RoleRepository;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ChannelsRelationManager extends RelationManager
{
    protected static string $relationship = 'channels';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __("filament.relation_manager.channels.title");
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Channel')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('pivot.is_user_verified')
                    ->label('Verified')
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('revokeAccess')
                    ->visible(
                        app(RoleRepository::class)->hasRole(
                            auth()->user(),
                            RoleEnum::SUPER_ADMIN,
                            GuardEnum::DEFAULT
                        )
                    )
                    ->label(__('filament.user_revoke_channel_access.label'))
                    ->requiresConfirmation()
                    ->action(function (Channel $record): void {
                        app(RevokeChannelAccess::class)->handle(
                            $this->getOwnerRecord(),
                            $record,
                            auth()->user()
                        );
                    })
                    ->successNotificationTitle(__('filament.user_revoke_channel_access.success_notification_title')),
            ]);
    }
}

