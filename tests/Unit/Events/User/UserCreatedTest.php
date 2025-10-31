<?php

declare(strict_types=1);

namespace Tests\Unit\Events\User;

use App\Events\User\UserCreated;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserCreatedTest extends TestCase
{
    public function testItStoresUserInstanceAndDefaultValues(): void
    {
        // Arrange
        $user = new User();
        $user->forceFill(['id' => 1, 'email' => 'test@example.com']);

        // Act
        $event = new UserCreated($user);

        // Assert
        $this->assertSame($user, $event->user, 'User instance should match the constructor argument');
        $this->assertFalse($event->fromBackend, 'Default value for fromBackend should be false');
        $this->assertNull($event->plainPassword, 'Default value for plainPassword should be null');
    }

    public function testItStoresAllProvidedArguments(): void
    {
        // Arrange
        $user = new User();
        $user->forceFill(['id' => 5, 'email' => 'backend@example.com']);

        $fromBackend = true;
        $plainPassword = 'secret123';

        // Act
        $event = new UserCreated($user, $fromBackend, $plainPassword);

        // Assert
        $this->assertSame($user, $event->user);
        $this->assertTrue($event->fromBackend);
        $this->assertSame('secret123', $event->plainPassword);
    }

    public function testItAllowsNullPasswordEvenWhenFromBackendIsTrue(): void
    {
        // Arrange
        $user = new User();
        $user->forceFill(['id' => 9]);

        // Act
        $event = new UserCreated($user, true, null);

        // Assert
        $this->assertTrue($event->fromBackend);
        $this->assertNull($event->plainPassword);
    }
}
