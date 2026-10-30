<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Changelog;

use App\Exceptions\Changelog\UnknownChangeTypeException;
use App\Support\Changelog\DatedRelease;
use App\Support\Changelog\KeepAChangelogFormatter;
use MarkWalet\Changelog\Change;
use MarkWalet\Changelog\Feature;
use PHPUnit\Framework\TestCase;

final class KeepAChangelogFormatterTest extends TestCase
{
    public function testOrdersSectionsCanonicallyAndKeepsTheWrittenOrderWithinASection(): void
    {
        $release = $this->release('4.12.3', '2026-10-30', [
            new Change('security', '**Packages**: upgraded'),
            new Change('fixed', '**Zeta**: second fix'),
            new Change('added', '**Feature**: new'),
            new Change('fixed', '**Alpha**: first fix'),
        ]);

        $formatted = (new KeepAChangelogFormatter())->single($release);

        $this->assertSame(implode(PHP_EOL, [
            '## [4.12.3] - 2026-10-30',
            '',
            '### Added',
            '- **Feature**: new',
            '',
            '### Fixed',
            '- **Zeta**: second fix',
            '- **Alpha**: first fix',
            '',
            '### Security',
            '- **Packages**: upgraded',
        ]), $formatted);
    }

    public function testUnreleasedVersionHasNoDate(): void
    {
        $formatted = (new KeepAChangelogFormatter())->single($this->release('unreleased', null, []));

        $this->assertSame('## [Unreleased]', $formatted);
    }

    public function testWrapsSingleLineMessagesAtTheConfiguredWidth(): void
    {
        $release = $this->release('unreleased', null, [new Change('added', 'one two three four five six')]);

        $formatted = (new KeepAChangelogFormatter(['line_width' => 16]))->single($release);

        $this->assertStringEndsWith(implode(PHP_EOL, [
            '- one two three',
            '  four five six',
        ]), $formatted);
    }

    public function testKeepsMultiLineMessagesAsWritten(): void
    {
        $message = "**Packages**\n    - upgraded packages to newest version (e.g. laravel or npm dependencies)";
        $release = $this->release('unreleased', null, [new Change('security', $message)]);

        $formatted = (new KeepAChangelogFormatter(['line_width' => 40]))->single($release);

        $this->assertStringEndsWith(implode(PHP_EOL, [
            '- **Packages**',
            '    - upgraded packages to newest version (e.g. laravel or npm dependencies)',
        ]), $formatted);
    }

    public function testRejectsTypesOutsideKeepAChangelog(): void
    {
        $release = $this->release('4.12.3', null, [new Change('improved', 'x')]);

        $this->expectException(UnknownChangeTypeException::class);

        (new KeepAChangelogFormatter())->single($release);
    }

    /**
     * @param string $version
     * @param string|null $releasedOn
     * @param list<Change> $changes
     * @return DatedRelease
     */
    private function release(string $version, ?string $releasedOn, array $changes): DatedRelease
    {
        $release = new DatedRelease($version, $releasedOn);
        $release->add(new Feature($changes));

        return $release;
    }
}
