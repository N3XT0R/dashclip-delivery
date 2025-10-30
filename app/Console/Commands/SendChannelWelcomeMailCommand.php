<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\ChannelWelcomeMail;
use App\Models\Channel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendChannelWelcomeMailCommand extends Command
{
    /**
     * Command signature.
     *
     * Beispiele:
     *  php artisan channels:send-welcome
     *  php artisan channels:send-welcome 5
     *  php artisan channels:send-welcome test@example.com
     *  php artisan channels:send-welcome --dry
     */
    protected $signature = 'channels:send-welcome
                            {channel? : Channel ID oder E-Mail-Adresse}
                            {--force : Sende auch, wenn bereits approved}
                            {--dry : Zeigt nur an, wer eine Mail erhalten würde (keine Sendung)}';

    /**
     * Command description.
     */
    protected $description = 'Sendet Willkommens-/Freigabe-Mails an Kanäle oder zeigt sie als Vorschau (--dry).';

    public function handle(): int
    {
        $arg = $this->argument('channel');
        $force = $this->option('force');
        $dry = $this->option('dry');

        // Basisselektion
        $query = Channel::query();

        if ($arg) {
            if (is_numeric($arg)) {
                $query->where('id', (int)$arg);
            } else {
                $query->where('email', $arg);
            }
        } elseif (!$force) {
            $query->whereNull('approved_at');
        }

        $channels = $query->get();

        if ($channels->isEmpty()) {
            $this->warn('Keine passenden Kanäle gefunden.');
            return self::SUCCESS;
        }

        // Vorschau anzeigen
        if ($dry) {
            $this->info('🧪 Dry-Run: Es würden folgende Kanäle angeschrieben werden:');
            $this->table(
                ['ID', 'Name', 'E-Mail', 'Approved At'],
                $channels->map(fn($c) => [
                    $c->id,
                    $c->name,
                    $c->email,
                    $c->approved_at?->toDateTimeString() ?? '—',
                ])
            );

            $this->comment('Gesamt: '.$channels->count().' Kanal(e)');
            return self::SUCCESS;
        }

        // Versand durchführen
        $this->info('📬 Sende Willkommens-Mail(s) an '.$channels->count().' Kanal(e)...');
        $bar = $this->output->createProgressBar($channels->count());
        $bar->start();

        foreach ($channels as $channel) {
            try {
                Mail::to($channel->email)->send(new ChannelWelcomeMail($channel));
            } catch (\Throwable $e) {
                $this->error("\nFehler beim Versand an {$channel->email}: {$e->getMessage()}");
                report($e);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Versand abgeschlossen.');

        return self::SUCCESS;
    }
}
