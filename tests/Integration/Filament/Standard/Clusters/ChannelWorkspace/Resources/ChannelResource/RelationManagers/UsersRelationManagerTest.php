<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\RelationManagers;

use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\RelationManagers\UsersRelationManager;
use App\Models\Channel;
use Filament\Tables\Table;
use Tests\DatabaseTestCase;

final class UsersRelationManagerTest extends DatabaseTestCase
{
    public function testRelationshipNameAndTitle(): void
    {
        $channel = Channel::factory()->create();

        self::assertSame('channelUsers', UsersRelationManager::getRelationshipName());
        self::assertSame(
            __('channel-workspace.channel_resource.relation_manager.users.title'),
            UsersRelationManager::getTitle($channel, '')
        );
    }

    public function testTableColumnsAndActions(): void
    {
        $channel = Channel::factory()->create();

        $manager = new UsersRelationManager();
        $manager->ownerRecord = $channel;

        $table = $manager->table(Table::make());

        $columns = array_values($table->getColumns());

        self::assertSame([
            'name',
            'email',
            'pivot.is_user_verified',
        ], array_map(fn ($column) => $column->getName(), $columns));

        $actions = array_values($table->getRecordActions());

        self::assertCount(1, $actions);
        self::assertSame('revokeAccess', $actions[0]->getName());
        self::assertSame(__('filament.user_revoke_channel_access.label'), $actions[0]->getLabel());
    }
}
