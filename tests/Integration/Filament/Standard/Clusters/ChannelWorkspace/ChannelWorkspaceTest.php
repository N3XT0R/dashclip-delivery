<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace;

use App\Filament\Standard\Clusters\ChannelWorkspace\ChannelWorkspace;
use Tests\DatabaseTestCase;

final class ChannelWorkspaceTest extends DatabaseTestCase
{
    public function testNavigationMetadataUsesTranslatedLabels(): void
    {
        $this->assertSame(
            __('nav.channel_owner'),
            ChannelWorkspace::getNavigationGroup(),
        );

        $this->assertSame(
            __('channel-workspace.title'),
            ChannelWorkspace::getNavigationLabel(),
        );

        $this->assertSame(
            __('channel-workspace.title'),
            ChannelWorkspace::getClusterBreadcrumb(),
        );

        $this->assertSame(
            __('channel-workspace.title'),
            (new ChannelWorkspace())->getTitle(),
        );
    }
}
