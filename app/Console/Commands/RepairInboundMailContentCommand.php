<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enum\MailDirection;
use App\Models\MailLog;
use App\Services\Mail\RawMailBodyDecoderService;
use Illuminate\Console\Command;

/**
 * Repair inbound mail entries that were stored before the reading fix.
 *
 * Those entries hold the raw message, so the log shows MIME boundaries or
 * base64 instead of the text. The raw payload carries everything needed, which
 * makes the repair possible in place.
 */
class RepairInboundMailContentCommand extends Command
{
    protected $signature = 'mail:repair-inbound-content {--dry-run : Report what would change without writing}';

    protected $description = 'Decode inbound mail bodies that were stored in their raw transport form';

    /**
     * @param RawMailBodyDecoderService $decoder
     * @return int
     */
    public function handle(RawMailBodyDecoderService $decoder): int
    {
        $repaired = 0;
        $skipped = 0;
        $failed = 0;
        $dryRun = (bool)$this->option('dry-run');

        foreach (MailLog::query()->where('direction', MailDirection::INBOUND)->cursor() as $log) {
            $meta = $log->meta ?? [];

            if (isset($meta['content_format'])) {
                $skipped++;
                continue;
            }

            $decoded = $decoder->decode((string)($meta['content'] ?? ''));

            if ($decoded === null) {
                $failed++;
                $this->warn(sprintf('Entry %d could not be decoded and was left as it is.', $log->getKey()));
                continue;
            }

            $repaired++;

            if ($dryRun) {
                continue;
            }

            $meta['content'] = $decoded['content'];
            $meta['content_format'] = $decoded['format'];
            $log->meta = $meta;
            $log->save();
        }

        $this->info(sprintf(
            '%s%d repaired, %d already readable, %d left untouched.',
            $dryRun ? 'Dry run: ' : '',
            $repaired,
            $skipped,
            $failed,
        ));

        return self::SUCCESS;
    }
}
