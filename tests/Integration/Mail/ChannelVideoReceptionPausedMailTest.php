<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Enum\TokenPurposeEnum;
use App\Mail\ChannelVideoReceptionPausedMail;
use App\Models\Channel;
use Carbon\Carbon;
use Tests\DatabaseTestCase;

final class ChannelVideoReceptionPausedMailTest extends DatabaseTestCase
{
    public function testEnvelopeContainsChannelNameInSubject(): void
    {
        $channel = Channel::factory()->create(['name' => 'My Channel']);

        $mail = new ChannelVideoReceptionPausedMail(
            channel: $channel,
            plainToken: 'abc123',
            expireAt: Carbon::now()->addMonth(),
        );

        $this->assertStringContainsString('My Channel', $mail->envelope()->subject);
    }

    public function testViewDataContainsAllExpectedKeys(): void
    {
        $channel = Channel::factory()->create(['name' => 'My Channel']);
        $expireAt = Carbon::now()->addMonth();

        $mail = new ChannelVideoReceptionPausedMail(
            channel: $channel,
            plainToken: 'abc123',
            expireAt: $expireAt,
        );

        $data = $mail->content()->with;

        $this->assertArrayHasKey('channel', $data);
        $this->assertArrayHasKey('expireAt', $data);
        $this->assertArrayHasKey('reactivateUrl', $data);
        $this->assertStringContainsString(
            TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value,
            $data['reactivateUrl']
        );
        $this->assertStringContainsString('abc123', $data['reactivateUrl']);
    }
}
