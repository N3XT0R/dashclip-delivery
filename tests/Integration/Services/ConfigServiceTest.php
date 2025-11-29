<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\Config;
use App\Services\ConfigService;
use App\Repository\Contracts\ConfigRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Mockery\MockInterface;
use Tests\DatabaseTestCase;

class ConfigServiceTest extends DatabaseTestCase
{
    private CacheRepository $cache;

    private ConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->app->make('cache.store');
        $this->cache->clear();

        $this->service = $this->app->make(ConfigService::class);
    }

    public function testGetReturnsCachedValueWithoutTouchingRepository(): void
    {
        $cacheKey = 'configs::default::site.name';
        $this->cache->forever($cacheKey, 'cached-value');

        $this->mock(ConfigRepositoryInterface::class, static function (MockInterface $mock): void {
            $mock->shouldNotReceive('findByKeyAndCategory');
        });

        $service = $this->app->make(ConfigService::class);

        self::assertSame('cached-value', $service->get('site.name'));
    }

    public function testGetFallsBackToRepositoryAndWarmsCache(): void
    {
        $config = Config::factory()->create([
            'key' => 'ui.theme',
            'value' => 'dark',
            'cast_type' => 'string',
            'config_category_id' => null,
        ]);

        $value = $this->service->get('ui.theme');

        self::assertSame('dark', $value);
        self::assertSame('dark', $this->cache->get('configs::default::ui.theme'));
        self::assertSame('default', $this->cache->get('configs::map::ui.theme'));
        self::assertTrue($this->cache->get('configs::exists::default::ui.theme'));

        // ensure the config was fetched from repository when not cached
        $this->assertTrue($config->is($config->fresh()));
    }

    public function testHasPrefersCacheFlagsBeforeRepositoryLookup(): void
    {
        $existsKey = 'configs::exists::default::site.locale';
        $this->cache->forever($existsKey, true);

        $this->mock(ConfigRepositoryInterface::class, static function (MockInterface $mock): void {
            $mock->shouldNotReceive('existsByKeyAndCategory');
        });

        $service = $this->app->make(ConfigService::class);

        self::assertTrue($service->has('site.locale'));
    }

    public function testDeleteRemovesCachedEntriesUsingMappedSlugWhenConfigMissing(): void
    {
        $cacheKey = 'configs::custom::feature.flags';
        $mapKey = 'configs::map::feature.flags';

        $this->cache->forever($cacheKey, ['cached' => true]);
        $this->cache->forever($mapKey, 'custom');

        $this->mock(ConfigRepositoryInterface::class, static function (MockInterface $mock): void {
            $mock->shouldReceive('findByKeyAndCategory')
                ->once()
                ->with('feature.flags', 'custom')
                ->andReturn(null);

            $mock->shouldReceive('deleteByKeyAndCategory')
                ->once()
                ->with('feature.flags', 'custom')
                ->andReturn(1);
        });

        $service = $this->app->make(ConfigService::class);

        self::assertTrue($service->delete('feature.flags', 'custom'));
        self::assertNull($this->cache->get($cacheKey));
        self::assertNull($this->cache->get($mapKey));
    }
}
