<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Facades\Cfg;
use App\Mail\NewOfferMail;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Config;
use App\Services\Contracts\ConfigServiceInterface;
use BadMethodCallException;
use Carbon\Carbon;
use Tests\TestCase;

class NewOfferMailTest extends TestCase
{
    public function testEnvelopeUsesConfigForBccAndSubject(): void
    {
        $this->fakeConfig([
            'email' => [
                'email_admin_mail' => 'admin@example.com',
                'email_get_bcc_notification' => true,
            ],
        ]);

        $batch = new Batch();
        $batch->setAttribute('id', 7);

        $channel = new Channel(['name' => 'Creator Channel']);

        $mail = new NewOfferMail(
            $batch,
            $channel,
            'https://example.com/offers/7',
            Carbon::parse('2024-10-05 12:00:00'),
            'https://example.com/offers/7/unused',
            false
        );

        $envelope = $mail->envelope();

        $this->assertSame('Neue Videos verfügbar – Batch #7', $envelope->subject);
        $this->assertCount(1, $envelope->bcc);
        $this->assertSame('admin@example.com', $envelope->bcc[0]->address ?? null);
    }

    public function testContentProvidesTemplateDataAndSubject(): void
    {
        $this->fakeConfig([
            'email' => [
                'email_admin_mail' => 'admin@example.com',
                'email_get_bcc_notification' => false,
            ],
        ]);

        $batch = new Batch();
        $batch->setAttribute('id', 9);

        $channel = new Channel(['name' => 'Main Channel']);
        $expiresAt = Carbon::parse('2024-10-07 10:00:00');

        $mail = new NewOfferMail(
            $batch,
            $channel,
            'https://example.com/offers/9',
            $expiresAt,
            'https://example.com/offers/9/unused',
            false
        );

        $content = $mail->content();

        $this->assertSame('emails.new-offer', $content->view);
        $this->assertSame('Neue Videos verfügbar – Batch #9', $content->with['subject']);
        $this->assertSame($batch, $content->with['batch']);
        $this->assertSame($channel, $content->with['channel']);
        $this->assertSame('https://example.com/offers/9', $content->with['offerUrl']);
        $this->assertTrue($expiresAt->equalTo($content->with['expiresAt']));
        $this->assertSame('https://example.com/offers/9/unused', $content->with['unusedUrl']);
    }

    private function fakeConfig(array $values): void
    {
        $service = new class($values) implements ConfigServiceInterface {
            public function __construct(private array $values)
            {
            }

            public function get(
                string $key,
                ?string $category = null,
                mixed $default = null,
                bool $withoutCache = false
            ): mixed {
                if ($category !== null) {
                    return $this->values[$category][$key] ?? $default;
                }

                return $this->values[$key] ?? $default;
            }

            public function set(
                string $key,
                mixed $value,
                ?string $category = null,
                string $castType = 'string',
                bool $isVisible = true
            ): Config {
                throw new BadMethodCallException('ConfigService stub does not support set().');
            }

            public function has(string $key, ?string $category = null): bool
            {
                if ($category !== null) {
                    return array_key_exists($key, $this->values[$category] ?? []);
                }

                return array_key_exists($key, $this->values);
            }
        };

        Cfg::swap($service);
    }
}
