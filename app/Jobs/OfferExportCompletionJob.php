<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Filament\Standard\Exports\OfferExporter;
use App\Models\User;
use App\Notifications\OfferDownloadReadyNotification;
use App\Repository\ChannelRepository;
use Filament\Actions\Exports\Jobs\ExportCompletion;

/**
 * Completes exports like Filament does and additionally mails the link of a ready offer download.
 *
 * Bound in place of Filament's ExportCompletion, so the mail only goes out once the export is marked
 * complete and its link works. Other exports are completed unchanged.
 */
class OfferExportCompletionJob extends ExportCompletion
{
    public function handle(): void
    {
        parent::handle();

        $user = $this->export->user;
        if (
            $this->export->exporter !== OfferExporter::class
            || (int)$this->export->successful_rows < 1
            || !$user instanceof User
        ) {
            return;
        }

        $channel = app(ChannelRepository::class)->findById((int)($this->options['channel_id'] ?? 0));

        $user->notify(new OfferDownloadReadyNotification($this->export, $this->authGuard, $channel));
    }
}
