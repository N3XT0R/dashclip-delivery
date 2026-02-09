<?php

namespace App\Filament\Resources;

use App\Enum\Users\RoleEnum;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\ChannelsRelationManager;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    public static function getNavigationBadge(): ?string
    {
        return auth()->user()->hasRole(RoleEnum::SUPER_ADMIN->value) ? static::getModel()::count() : null;
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->unique('users')
                    ->required(),
                Forms\Components\TextInput::make('submitted_name')
                    ->unique('users'),
                Forms\Components\TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password(),
                Forms\Components\Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->getOptionLabelFromRecordUsing(
                        fn(Role $record): string => "{$record->name} ({$record->guard_name})"
                    ),
                Forms\Components\Textarea::make('app_authentication_secret')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('app_authentication_recovery_codes')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('has_email_authentication')
                    ->required(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ChannelsRelationManager::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('User')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('submitted_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->colors(['success']),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('has_email_authentication')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\Action::make('activities')->url(
                    fn($record) => self::getUrl('activities',
                        ['record' => $record])
                ),
                Actions\Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->action(function (User $record) {
                        $password = Str::password(12);
                        $record->update(['password' => bcrypt($password)]);
                        Notification::make()
                            ->title('Password reset to "'.$password.'"')
                            ->success()
                            ->send();
                    })
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'activities' => Pages\ListUserActivities::route('/{record}/activities'),
        ];
    }
}
