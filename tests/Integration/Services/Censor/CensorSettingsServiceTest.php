<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Censor;

use App\Constants\Config\CensorConfigEntry;
use App\Exceptions\Censor\VideoCensorException;
use App\Models\Config;
use App\Services\Censor\CensorSettingsService;
use App\Services\Contracts\ConfigServiceInterface;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;

final class CensorSettingsServiceTest extends DatabaseTestCase
{
    public function testReadsTypedDatabaseValuesAndRefreshesBetweenVideos(): void
    {
        $config = $this->app->make(ConfigServiceInterface::class);
        $config->set(CensorConfigEntry::FRAME_STEP, 6, 'censor', 'int');
        $config->set(CensorConfigEntry::CONFIDENCE, 0.4, 'censor', 'float');
        $settings = $this->app->make(CensorSettingsService::class);
        self::assertSame(6, $settings->current()['frame_step']);
        self::assertSame(0.4, $settings->current()['confidence']);

        Config::query()->where('key', CensorConfigEntry::FRAME_STEP)->update(['value' => '9']);
        self::assertSame(9, $settings->current()['frame_step']);
    }

    public function testMissingEntriesUseDefaultsRatherThanLegacyEnvironmentValues(): void
    {
        Config::query()->whereIn('key', array_keys(CensorConfigEntry::RULES))->delete();
        config()->set('censor.initial_settings.frame_step', 30);

        self::assertSame([
            'columns' => 3, 'rows' => 2, 'frame_step' => 3,
            'confidence' => 0.15, 'margin' => 0.25, 'timeout_seconds' => 3600,
        ], $this->app->make(CensorSettingsService::class)->current());
    }

    #[DataProvider('invalidSettings')]
    public function testInvalidValuesCannotBeSaved(string $key, int|float $value, string $type): void
    {
        $this->expectException(ValidationException::class);
        $this->app->make(ConfigServiceInterface::class)->set($key, $value, 'censor', $type);
    }

    /** @return iterable<string, array{string, int|float, string}> */
    public static function invalidSettings(): iterable
    {
        yield 'zero frame interval' => [CensorConfigEntry::FRAME_STEP, 0, 'int'];
        yield 'fractional interval' => [CensorConfigEntry::FRAME_STEP, 1.5, 'int'];
        yield 'too many columns' => [CensorConfigEntry::COLUMNS, 9, 'int'];
        yield 'zero rows' => [CensorConfigEntry::ROWS, 0, 'int'];
        yield 'negative margin' => [CensorConfigEntry::MARGIN, -0.1, 'float'];
        yield 'invalid confidence' => [CensorConfigEntry::CONFIDENCE, 1.1, 'float'];
        yield 'unlimited timeout' => [CensorConfigEntry::TIMEOUT, 0, 'int'];
    }

    public function testInvalidStoredValuesFailBeforeProcessing(): void
    {
        Config::query()->where('key', CensorConfigEntry::FRAME_STEP)->update(['value' => '0']);

        $this->expectException(VideoCensorException::class);
        $this->app->make(CensorSettingsService::class)->current();
    }
}
