<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace\Pages;

use App\Filament\Standard\Clusters\ChannelWorkspace\ChannelWorkspace;
use App\Filament\Standard\Clusters\ChannelWorkspace\Pages\ChannelOAuthSettings;
use Tests\TestCase;

final class ChannelOAuthSettingsTest extends TestCase
{
    public function testClusterAndViewConfiguration(): void
    {
        $page = new ChannelOAuthSettings();

        self::assertSame(ChannelWorkspace::class, $page::getCluster());
        self::assertSame('filament.standard.pages.channel-o-auth-settings', $page->getView());
        self::assertFalse(ChannelOAuthSettings::canAccess());
    }
}
