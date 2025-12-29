<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages;

use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages\EditChannel;
use Filament\Actions;
use Tests\TestCase;

final class EditChannelTest extends TestCase
{
    public function testResourceAndHeaderActions(): void
    {
        $page = new EditChannel();

        self::assertSame(ChannelResource::class, $page::getResource());

        $actions = $page->getHeaderActions();

        self::assertCount(1, $actions);
        self::assertInstanceOf(Actions\ViewAction::class, $actions[0]);
    }
}
