<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ChannelService;
use Illuminate\Console\Command;

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

    public function __construct(
        private readonly ChannelService $channelService
    ) {
        parent::__construct();
    }

    /**
     * Führt den Artisan-Befehl aus.
     */
    public function handle(): int
    {
        $arg = $this->argument('channel');
        $force = $this->option('force');
        $dry = $this->option('dry');

        // Ermittelt die passenden Kanäle über den Service
        $channels = $this->channelService->getEligibleForWelcomeMail($arg, $force);

        if ($channels->isEmpty()) {
            $this->warn('Keine passenden Kanäle gefunden.');
            return self::SUCCESS;
        }

        // Vorschau (Dry-Run)
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

        // Tatsächlicher Versand
        $this->info('📬 Sende Willkommens-Mail(s) an '.$channels->count().' Kanal(e)...');
        $bar = $this->output->createProgressBar($channels->count());
        $bar->start();

        $sent = $this->channelService->sendWelcomeMails($channels);

        $bar->finish();
        $this->newLine(2);

        $this->info('Versand abgeschlossen. ('.count($sent).' Mail(s) gesendet)');

        return self::SUCCESS;
    }
}
