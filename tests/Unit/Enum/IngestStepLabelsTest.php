<?php

declare(strict_types=1);

namespace Tests\Unit\Enum;

use App\Enum\Ingest\IngestStepEnum;
use Tests\TestCase;

/**
 * Every step of the preparation is named in the status overview, in both languages.
 */
final class IngestStepLabelsTest extends TestCase
{
    public function testEveryStepIsNamedInEveryLanguage(): void
    {
        foreach (['de', 'en'] as $locale) {
            $this->app->setLocale($locale);

            foreach (IngestStepEnum::order() as $step) {
                $key = 'ingest.steps.' . $step->value;
                self::assertNotSame($key, __($key), "{$key} has no label in {$locale}");
            }
        }
    }
}
