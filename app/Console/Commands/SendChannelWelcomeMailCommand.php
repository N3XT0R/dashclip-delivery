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
     * The name and signature of the console command.
     *
     * You can pass an optional channel ID or email:
     *   php artisan channels:send-welcome
     *   php artisan channels:send-welcome 5
     *   php artisan channels:send-welcome test@example.com
     */
    protected $signature = 'channels:send-welcome {channel? : Channel ID or email} {--force : Send even if already approved}';

    /**
     * The console command description.
     */
    protected $description = 'Send welcome/approval mail to a specific or all unapproved channels.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $arg = $this->argument('channel');
        $force = $this->option('force');

        // Target selection
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
            $this->info('No matching channels found.');
            return self::SUCCESS;
        }

        $this->info('Sending welcome mail(s) to '.$channels->count().' channel(s)...');

        $bar = $this->output->createProgressBar($channels->count());
        $bar->start();

        foreach ($channels as $channel) {
            try {
                Mail::to($channel->email)->send(new ChannelWelcomeMail($channel));
            } catch (\Throwable $e) {
                $this->error("\nFailed to send mail to {$channel->email}: {$e->getMessage()}");
                report($e);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Done.');

        return self::SUCCESS;
    }
}
