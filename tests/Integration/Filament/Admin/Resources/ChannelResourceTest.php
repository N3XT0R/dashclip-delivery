<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Channels\ChannelResource;
use App\Filament\Admin\Resources\Channels\Pages\ListChannels;
use Filament\Tables\Table;
use Tests\TestCase;

class ChannelResourceTest extends TestCase
{
    public function testNameColumnIsSearchable(): void
    {
        $page = app(ListChannels::class);
        $table = ChannelResource::table(Table::make($page));

        $column = $table->getColumn('name');

        $this->assertTrue($column->isSearchable());
    }
}
