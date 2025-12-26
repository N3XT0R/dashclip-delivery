<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ChannelsRelationManager extends RelationManager
{
    protected static string $relationship = 'channels';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Channel')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('email')
                    ->searchable(),

                Tables\Columns\IconColumn::make('pivot.is_user_verified')
                    ->label('Verified')
                    ->boolean(),
            ])
            ->recordActions([
            ]);
    }
}

