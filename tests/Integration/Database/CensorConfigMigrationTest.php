<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use App\Constants\Config\CensorConfigEntry;
use App\Models\Config;
use Database\Seeders\CensorConfigSeeder;
use App\Services\Contracts\ConfigServiceInterface;
use Illuminate\Database\Migrations\Migration;
use Tests\DatabaseTestCase;

final class CensorConfigMigrationTest extends DatabaseTestCase
{
    public function testImportsExistingSettingsOnceAndPreservesSubsequentEdits(): void
    {
        $migration = $this->migration();
        $migration->down();
        config()->set('censor.initial_settings.frame_step', 7);
        config()->set('censor.initial_settings.confidence', 0.3);
        $migration->up();
        $config = $this->app->make(ConfigServiceInterface::class);
        self::assertSame(7, $config->get(CensorConfigEntry::FRAME_STEP, 'censor', withoutCache: true));
        self::assertSame(0.3, $config->get(CensorConfigEntry::CONFIDENCE, 'censor', withoutCache: true));
        $this->assertDatabaseHas('config_categories', ['slug' => 'censor', 'is_visible' => true]);
        self::assertSame(
            count(CensorConfigEntry::RULES),
            Config::query()->whereIn('key', array_keys(CensorConfigEntry::RULES))->count()
        );

        $config->set(CensorConfigEntry::FRAME_STEP, 10, 'censor', 'int');
        $this->seed(CensorConfigSeeder::class);
        $migration->up();
        self::assertSame(10, $config->get(CensorConfigEntry::FRAME_STEP, 'censor', withoutCache: true));
    }

    public function testRollbackRemovesOnlyTheNewCategory(): void
    {
        $this->migration()->down();

        $this->assertDatabaseMissing('config_categories', ['slug' => 'censor']);
        self::assertSame(0, Config::query()->whereIn('key', array_keys(CensorConfigEntry::RULES))->count());
        $this->assertDatabaseHas('config_categories', ['slug' => 'default']);
    }

    public function testImportsValuesFromAnOlderConfigurationCache(): void
    {
        $migration = $this->migration();
        $migration->down();
        config()->set('censor', ['frame_step' => 8, 'tiles' => ['columns' => 4, 'rows' => 3]]);

        $migration->up();

        $config = $this->app->make(ConfigServiceInterface::class);
        self::assertSame(8, $config->get(CensorConfigEntry::FRAME_STEP, 'censor', withoutCache: true));
        self::assertSame(4, $config->get(CensorConfigEntry::COLUMNS, 'censor', withoutCache: true));
        self::assertSame(3, $config->get(CensorConfigEntry::ROWS, 'censor', withoutCache: true));
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_09_29_200152_add_censor_config_entries.php');
    }
}
