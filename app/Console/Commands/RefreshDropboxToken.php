<?php

namespace App\Console\Commands;

use App\Services\Dropbox\AutoRefreshTokenProvider;
use Illuminate\Console\Command;

class RefreshDropboxToken extends Command
{
    protected $signature = 'dropbox:refresh-token';
    
    protected $description = 'Updates the Dropbox access token and rotates the refresh token if necessary.';


    public function __construct(private AutoRefreshTokenProvider $provider)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $exitCode = self::SUCCESS;
        try {
            $this->provider->getToken();
            $this->info('Dropbox Token refreshed');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            $exitCode = self::FAILURE;
        }
        return $exitCode;
    }
}
