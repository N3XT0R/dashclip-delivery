<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Facades\Cfg;
use App\Mail\ReminderMail;
use App\Models\Channel;
use App\Models\Config;
use App\Services\Contracts\ConfigServiceInterface;
use BadMethodCallException;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ReminderMailTest extends TestCase
{
    public function testEnvelopeUsesConfigForBccAndSubject(): void
    {
        $this->fakeConfig([
            'email' => [
                'email_admin_mail' => 'admin@example.com',
                'email_get_bcc_notification' => true,
            ],
        ]);

        $channel = new Channel(['name' => 'Reminder Channel']);

        $mail = new ReminderMail(
            $channel,
            'https://example.com/reminders/1',
            Carbon::parse('2024-10-06 08:30:00'),
            Collection::make(['assignment-a']),
        );

        $envelope = $mail->envelope();

        $this->assertSame('Erinnerung: Angebote laufen bald ab', $envelope->subject);
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

        $channel = new Channel(['name' => 'Reminder Channel']);
        $expiresAt = Carbon::parse('2024-10-08 09:00:00');
        $assignments = Collection::make(['assignment-a', 'assignment-b']);

        $mail = new ReminderMail(
            $channel,
            'https://example.com/reminders/2',
            $expiresAt,
            $assignments,
        );

        $content = $mail->content();

        $this->assertSame('emails.reminder', $content->view);
        $this->assertSame('Erinnerung: Angebote laufen bald ab', $content->with['subject']);
        $this->assertSame($channel, $content->with['channel']);
        $this->assertSame('https://example.com/reminders/2', $content->with['offerUrl']);
        $this->assertTrue($expiresAt->equalTo($content->with['expiresAt']));
        $this->assertSame($assignments, $content->with['assignments']);
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
