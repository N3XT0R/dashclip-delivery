<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Mail;

use App\Models\User;
use App\Support\Mail\MailAddressResolver;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Config;

class MailAddressResolverTest extends TestCase
{
    protected MailAddressResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = $this->app->make(MailAddressResolver::class);
    }

    public function testResolvesEmailFromStringInProduction(): void
    {
        $this->app['env'] = 'production';

        $result = $this->resolver->resolve('user@example.com');

        $this->assertSame('user@example.com', $result);
    }

    public function testAppliesCatchAllInTestingEnvironment(): void
    {
        Config::set('mail.catch_all', 'catchall@example.com');

        $result = $this->resolver->resolve('user@example.com');

        $this->assertSame('catchall@example.com', $result);
    }

    public function testAppliesCatchAllForUserInTestingEnvironment(): void
    {
        $this->app['env'] = 'testing';
        Config::set('mail.catch_all', 'catchall@example.com');

        $user = User::factory()->make([
            'email' => 'user@example.com',
        ]);

        $result = $this->resolver->resolve($user);

        $this->assertSame('catchall@example.com', $result);
    }

    public function testFallsBackToOriginalEmailWhenNoCatchAllConfigured(): void
    {
        $this->app['env'] = 'testing';
        Config::set('mail.catch_all', null);

        $result = $this->resolver->resolve('user@example.com');

        $this->assertSame('user@example.com', $result);
    }

    public function testResolvesNullRecipientToEmptyStringWhenCatchAllConfigured(): void
    {
        $this->app['env'] = 'testing';
        Config::set('mail.catch_all', 'catchall@example.com');

        $result = $this->resolver->resolve(null);

        $this->assertSame('catchall@example.com', $result);
    }

    public function testResolvesNullRecipientToEmptyStringInProduction(): void
    {
        $this->app['env'] = 'production';

        $result = $this->resolver->resolve(null);

        $this->assertSame('', $result);
    }
}
