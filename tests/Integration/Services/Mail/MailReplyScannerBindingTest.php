<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Mail;

use App\Services\Mail\Scanner\Handlers\BounceHandler;
use App\Services\Mail\Scanner\Handlers\ReplyHandler;
use App\Services\Mail\Scanner\MailReplyScanner;
use ReflectionProperty;
use Tests\TestCase;

class MailReplyScannerBindingTest extends TestCase
{
    public function testContainerResolvesSingletonWithExpectedHandlers(): void
    {
        $first = $this->app->make(MailReplyScanner::class);
        $second = $this->app->make(MailReplyScanner::class);

        $this->assertSame($first, $second, 'MailReplyScanner should be a singleton');

        $reflection = new ReflectionProperty(MailReplyScanner::class, 'handlers');
        $reflection->setAccessible(true);

        $handlers = iterator_to_array($reflection->getValue($first));

        $this->assertCount(2, $handlers);
        $this->assertInstanceOf(BounceHandler::class, $handlers[0]);
        $this->assertInstanceOf(ReplyHandler::class, $handlers[1]);
    }
}
