<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Facades\Cfg;
use App\Mail\NewOfferMail;
use App\Models\Batch;
use App\Models\Channel;
use Carbon\Carbon;
use Tests\DatabaseTestCase;

final class NewOfferMailTest extends DatabaseTestCase
{
    public function testEnvelopeSetsSubjectAndOptionalBcc(): void
    {
        $batch = Batch::factory()->create();
        $channel = Channel::factory()->create();

        Cfg::set('email_admin_mail', 'admin@example.test', 'email');
        Cfg::set('email_get_bcc_notification', true, 'email', 'bool');

        $mail = new NewOfferMail(
            batch: $batch,
            channel: $channel,
            offerUrl: 'https://example.test/offer',
            expiresAt: Carbon::parse('2025-08-20 12:00:00'),
            unusedUrl: 'https://example.test/unused',
        );

        $envelope = $mail->envelope();
        $this->assertSame(
            'Neue Videos verfügbar – Batch #' . $batch->getKey(),
            $envelope->subject
        );
    }

    public function testEnvelopeOmitsBccIfNotificationIsDisabled(): void
    {
        $batch = Batch::factory()->create();
        $channel = Channel::factory()->create();

        Cfg::set('email_admin_mail', 'admin@example.test', 'email');
        Cfg::set('email_get_bcc_notification', false, 'email', 'bool');

        $mail = new NewOfferMail(
            batch: $batch,
            channel: $channel,
            offerUrl: 'https://example.test/offer',
            expiresAt: Carbon::now(),
            unusedUrl: 'https://example.test/unused',
        );

        $envelope = $mail->envelope();

        $this->assertSame([], $envelope->bcc);
    }

    public function testViewDataContainsAllExpectedValues(): void
    {
        $batch = Batch::factory()->create();
        $channel = Channel::factory()->create();
        $expiresAt = Carbon::parse('2025-08-20 12:00:00');

        $mail = new NewOfferMail(
            batch: $batch,
            channel: $channel,
            offerUrl: 'https://example.test/offer',
            expiresAt: $expiresAt,
            unusedUrl: 'https://example.test/unused',
            isChannelOperator: true,
        );

        $data = $mail->viewData();

        $this->assertSame($batch, $data['batch']);
        $this->assertSame($channel, $data['channel']);
        $this->assertSame('https://example.test/offer', $data['offerUrl']);
        $this->assertTrue($data['expiresAt']->equalTo($expiresAt));
        $this->assertSame('https://example.test/unused', $data['unusedUrl']);
        $this->assertTrue($data['isChannelOperator']);
    }
}
