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
use App\Facades\Cfg;
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
            'confidence' => 0.15, 'margin' => 0.25, 'timeout_seconds' => 3600, 'threads' => 2,
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
        yield 'no thread at all' => [CensorConfigEntry::THREADS, 0, 'int'];
        yield 'more threads than any machine here has' => [CensorConfigEntry::THREADS, 17, 'int'];
    }

    public function testInvalidStoredValuesFailBeforeProcessing(): void
    {
        Config::query()->where('key', CensorConfigEntry::FRAME_STEP)->update(['value' => '0']);

        $this->expectException(VideoCensorException::class);
        $this->app->make(CensorSettingsService::class)->current();
    }

    public function testTheThreadBudgetFollowsTheMachineItRunsOn(): void
    {
        $this->app->make(ConfigServiceInterface::class)->set(CensorConfigEntry::THREADS, 3, 'censor', 'int');

        self::assertSame(3, $this->app->make(CensorSettingsService::class)->current()['threads']);
    }

    public function testTheBlurringIsOffUntilSomeoneTurnsItOn(): void
    {
        self::assertFalse(app(CensorSettingsService::class)->isEnabled());
    }

    public function testAnAdministratorCanTurnItOn(): void
    {
        Cfg::set(CensorConfigEntry::ENABLED, true, CensorConfigEntry::CATEGORY, 'bool');

        self::assertTrue(app(CensorSettingsService::class)->isEnabled());
    }
}
