<?php

namespace App\Mail;

use App\Facades\Cfg;
use App\Models\Channel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ReminderMail extends AbstractLoggedMail
{
    use Queueable, SerializesModels;

    public function __construct(
        public Channel $channel,
        public string $offerUrl,
        public Carbon $expiresAt,
        public Collection $assignments,
    ) {
    }

    protected function viewName(): string
    {
        return 'emails.reminder';
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
            subject: 'Erinnerung: Angebote laufen bald ab',
        );
    }

    protected function viewData(): array
    {
        return [
            'channel' => $this->channel,
            'offerUrl' => $this->offerUrl,
            'expiresAt' => $this->expiresAt,
            'assignments' => $this->assignments,
        ];
    }


    /**
     * @return ReminderMail
     * @deprecated Use envelope() and content() instead.
     */
    public function build(): ReminderMail
    {
        $mailTo = (string)Cfg::get('email_admin_mail', 'email');
        $notification = (bool)Cfg::get('email_get_bcc_notification', 'email');
        $reminder = $this->subject('Erinnerung: Angebote laufen bald ab')
            ->view('emails.reminder');

        if (true === $notification && !empty($mailTo)) {
            $reminder = $reminder->bcc($mailTo);
        }

        return $reminder;
    }
}
