<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enum\OfferExportFormatEnum;
use App\Models\Channel;
use Filament\Actions\Exports\Models\Export;

final class OfferDownloadReadyMail extends AbstractLoggedMail
{
    protected string $subjectLine;

    public function __construct(
        public Export $export,
        public string $authGuard,
        public ?Channel $channel,
    ) {
        $this->subjectLine = __('mails.offer_download_ready.subject');
    }

    /**
     * Absolute link to the prepared ZIP, signed like the button of the in-app notification.
     * @return string
     */
    public function downloadUrl(): string
    {
        return url(OfferExportFormatEnum::downloadPath($this->export, $this->authGuard));
    }

    protected function viewName(): string
    {
        return 'emails.offer-download-ready';
    }

    protected function viewData(): array
    {
        $ready = (int)$this->export->successful_rows;

        return [
            'user' => $this->export->user,
            'channelName' => $this->channel?->name,
            'ready' => $ready,
            'skipped' => max(0, (int)$this->export->total_rows - $ready),
            'downloadUrl' => $this->downloadUrl(),
        ];
    }
}
