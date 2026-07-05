<?php

namespace App\Filament\Admin\Resources;

use App\Enum\Users\RoleEnum;
use App\Filament\Admin\Resources\UserResource\RelationManagers\ChannelsRelationManager;
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

    protected static string|UnitEnum|null $navigationGroup = 'filament.admin.navigation.system';

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
                    ->label(__('filament.admin.labels.email_address'))
                    ->email()
                    ->required(),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password(),
                Forms\Components\Select::make('roles')
                    ->label(__('filament.admin.labels.roles'))
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->getOptionLabelFromRecordUsing(
                        fn (Role $record): string => "{$record->name} ({$record->guard_name})"
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
                    ->label(__('filament.admin.labels.email_address'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('filament.admin.labels.roles'))
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
                    fn (User $record) => static::getUrl(
                        'activities',
                        ['record' => $record]
                    )
                ),
                Actions\Action::make('resetPassword')
                    ->label(__('filament.admin.labels.reset_password'))
                    ->icon('heroicon-o-key')
                    ->action(function (User $record) {
                        $password = Str::password(12);
                        $record->update(['password' => bcrypt($password)]);
                        Notification::make()
                            ->title(__('filament.admin.messages.password_reset_to', ['password' => $password]))
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
            'index' => \App\Filament\Admin\Resources\UserResource\Pages\ListUsers::route('/'),
            'create' => \App\Filament\Admin\Resources\UserResource\Pages\CreateUser::route('/create'),
            'activities' => \App\Filament\Admin\Resources\UserResource\Pages\ListUserActivities::route(
                '/{record}/activities'
            ),
            'edit' => \App\Filament\Admin\Resources\UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
