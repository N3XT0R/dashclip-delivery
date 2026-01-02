<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonInterval;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class PassportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->defineTokenExpires();
        $this->defineScopes();
    }

    protected function defineTokenExpires(): void
    {
        Passport::enablePasswordGrant();
        Passport::tokensExpireIn(CarbonInterval::day());
        Passport::refreshTokensExpireIn(CarbonInterval::days(30));
        Passport::personalAccessTokensExpireIn(CarbonInterval::month());
    }

    protected function defineScopes(): void
    {
        Passport::tokensCan([
            'account:read' => 'View account information',
            'account:write' => 'Modify account information',
            'channels:read' => 'View channels',
            'channels:write' => 'Manage channels',
            'videos:read' => 'View videos',
            'videos:create' => 'Upload Video',
            'assignments:read' => 'View assignments',
            'assignments:download' => 'Download assignments',
        ]);
    }
}
