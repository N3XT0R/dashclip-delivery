<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserUploadProceedMail extends AbstractLoggedMail
{
  use Queueable, SerializesModels;

  /**
   * Create a new message instance.
   */
  public function __construct()
  {
      //
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
      return new Envelope(
          subject: 'User Upload Proceed Mail',
      );
  }

  protected function viewName(): string
  {
      return 'emails.view-name';
  }

  protected function viewData(): array
  {
      return [];
  }

  /**
   * Get the attachments for the message.
   *
   * @return array<int, \Illuminate\Mail\Mailables\Attachment>
   */
  public function attachments(): array
  {
      return [];
  }
}
