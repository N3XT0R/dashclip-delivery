<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace;

use App\Filament\Standard\Clusters\ChannelWorkspace\ChannelWorkspace;
use Tests\TestCase;

final class ChannelWorkspaceTest extends TestCase
{
    public function testNavigationTextsUseTranslations(): void
    {
        self::assertSame(
            __('nav.channel_owner'),
            ChannelWorkspace::getNavigationGroup()
        );

        self::assertSame(
            __('channel-workspace.title'),
            ChannelWorkspace::getNavigationLabel()
        );

        self::assertSame(
            __('channel-workspace.title'),
            (new ChannelWorkspace())->getTitle()
        );

        self::assertSame(
            __('channel-workspace.title'),
            ChannelWorkspace::getClusterBreadcrumb()
        );
    }
}
