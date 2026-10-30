<?php

declare(strict_types=1);

namespace App\Support\Changelog;

use App\Enum\Changelog\ChangeTypeEnum;
use App\Exceptions\Changelog\UnknownChangeTypeException;
use MarkWalet\Changelog\Change;
use MarkWalet\Changelog\Formatters\ChangelogFormatter;
use MarkWalet\Changelog\Release;

/**
 * Formats releases in the Keep a Changelog house style of CHANGELOG.md.
 *
 * Sections follow the canonical category order, released versions carry their date, single-line
 * messages are wrapped at the configured width and multi-line messages are kept as written.
 */
final class KeepAChangelogFormatter extends ChangelogFormatter
{
    private const int DEFAULT_LINE_WIDTH = 100;

    private const string CONTINUATION_INDENT = '  ';

    /**
     * @param Release $release
     * @return string
     * @throws UnknownChangeTypeException When a change uses a category outside Keep a Changelog.
     */
    public function single(Release $release): string
    {
        $lines = [$this->versionHeading($release)];

        foreach ($this->changesByType($release) as $type => $changes) {
            $lines[] = '';
            $lines[] = '### ' . ChangeTypeEnum::from($type)->heading();

            foreach ($changes as $change) {
                $lines[] = $this->entry($change);
            }
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param list<Release> $releases
     * @return string
     * @throws UnknownChangeTypeException When a change uses a category outside Keep a Changelog.
     */
    public function multiple(array $releases): string
    {
        return implode(PHP_EOL . PHP_EOL, array_map($this->single(...), $releases));
    }

    private function versionHeading(Release $release): string
    {
        if ($release->version() === DatedXmlReleaseAdapter::UNRELEASED) {
            return '## [Unreleased]';
        }

        $heading = '## [' . $release->version() . ']';

        if ($release instanceof DatedRelease && $release->releasedOn() !== null) {
            $heading .= ' - ' . $release->releasedOn();
        }

        return $heading;
    }

    /**
     * Group the changes of a release by type in canonical category order.
     *
     * @param Release $release
     * @return array<string, list<Change>>
     * @throws UnknownChangeTypeException
     */
    private function changesByType(Release $release): array
    {
        $grouped = array_fill_keys(array_column(ChangeTypeEnum::cases(), 'value'), []);

        foreach ($release->changes() as $change) {
            if (!array_key_exists($change->type(), $grouped)) {
                throw UnknownChangeTypeException::forType($change->type(), $release->version());
            }

            $grouped[$change->type()][] = $change;
        }

        return array_filter($grouped);
    }

    private function entry(Change $change): string
    {
        $message = trim($change->message());

        if (str_contains($message, "\n")) {
            return '- ' . implode(PHP_EOL, array_map(rtrim(...), explode("\n", $message)));
        }

        $width = (int)$this->config('line_width', self::DEFAULT_LINE_WIDTH) - strlen(self::CONTINUATION_INDENT);

        return '- ' . wordwrap($message, $width, PHP_EOL . self::CONTINUATION_INDENT);
    }
}
