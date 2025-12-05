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
     * Examples:
     *  php artisan channels:send-welcome
     *  php artisan channels:send-welcome 5
     *  php artisan channels:send-welcome test@example.com
     *  php artisan channels:send-welcome --dry
     */
    protected $signature = 'channels:send-welcome
                        {channel? : Channel ID or email address}
                        {--force : Send even if already approved}
                        {--dry : Preview who would receive an email (no sending)}';

    /**
     * Command description.
     */
    protected $description = 'Sends welcome/approval emails to channels or shows a preview (--dry).';


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
