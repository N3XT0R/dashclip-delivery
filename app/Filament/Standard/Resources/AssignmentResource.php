<?php

namespace App\Filament\Standard\Resources;

use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use App\Repository\UserRepository;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'nav.channel_owner';

    public static function canAccess(): bool
    {
        $user = app(UserRepository::class)->getCurrentUser();

        if (!$user) {
            return false;
        }

        return $user->hasRole(RoleEnum::CHANNEL_OPERATOR->value);
    }


    public static function getNavigationLabel(): string
    {
        return __('my_offers.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __(static::$navigationGroup);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            ])
            ->filters([
                //
            ])
            ->recordActions([
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignments::route('/'),
        ];
    }
}
