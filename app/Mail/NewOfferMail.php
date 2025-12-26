<?php

namespace App\Mail;

use App\Facades\Cfg;
use App\Models\{Batch, Channel};
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOfferMail extends AbstractLoggedMail
{
    use Queueable, SerializesModels;

    public function __construct(
        public Batch $batch,
        public Channel $channel,
        public string $offerUrl,
        public Carbon $expiresAt,
        public string $unusedUrl,
        public bool $isChannelOperator = false,
    ) {
    }

    protected function viewName(): string
    {
        return 'emails.new-offer';
    }

    public function envelope(): Envelope
    {
        $mailTo = (string)Cfg::get('email_admin_mail', 'email');
        $notification = (bool)Cfg::get('email_get_bcc_notification', 'email');

        $bcc = [];
        if ($notification && !empty($mailTo)) {
            $bcc[] = $mailTo;
        }

        return new Envelope(
            bcc: $bcc,
            subject: 'Neue Videos verfügbar – Batch #' . $this->batch->getKey(),
        );
    }

    public function viewData(): array
    {
        return [
            'batch' => $this->batch,
            'channel' => $this->channel,
            'offerUrl' => $this->offerUrl,
            'expiresAt' => $this->expiresAt,
            'unusedUrl' => $this->unusedUrl,
            'isChannelOperator' => $this->isChannelOperator,
        ];
    }


    /**
     * @return NewOfferMail
     * @deprecated Use envelope() and content() instead.
     */
    public function build(): NewOfferMail
    {
        $mailTo = (string)Cfg::get('email_admin_mail', 'email');
        $notification = (bool)Cfg::get('email_get_bcc_notification', 'email');
        $offerMail = $this->subject('Neue Videos verfügbar – Batch #' . $this->batch->getKey())
            ->view('emails.new-offer');

        if (true === $notification && !empty($mailTo)) {
            $offerMail = $offerMail
                ->bcc($mailTo);
        }

        return $offerMail;
    }
}
