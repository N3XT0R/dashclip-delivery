<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Filament\Panel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;

/**
 * Unit tests for the App\Models\User model.
 *
 * We validate:
 *  - fillable + hashed cast for password
 *  - hidden attributes in array/json
 *  - datetime cast for email_verified_at
 *  - Filament contract canAccessPanel() returns true
 */
final class UserTest extends DatabaseTestCase
{
    public function testFactoryCreatesUserWithHashedPasswordAndFillableAttributes(): void
    {
        // Arrange & Act
        $user = User::factory()->admin()->make([
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
            'password' => 'secret123', // "hashed" cast should hash this on save
        ]);

        // Assert: name/email persisted as provided
        $this->assertSame('Jane Doe', $user->name);
        $this->assertSame('jane@example.test', $user->email);

        // Assert: password is not stored in plain text and matches via Hash::check
        $this->assertNotSame('secret123', $user->password);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function testHiddenAttributesAreExcludedFromArrayAndJson(): void
    {
        $user = User::factory()->admin()->make([
            'password' => 'secret123',
            'remember_token' => 'abc123',
        ]);

        $asArray = $user->toArray();
        $this->assertArrayNotHasKey('password', $asArray);
        $this->assertArrayNotHasKey('remember_token', $asArray);

        $asJson = json_decode($user->toJson(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('password', $asJson);
        $this->assertArrayNotHasKey('remember_token', $asJson);
    }

    public function testEmailVerifiedAtIsCarbonInstanceWhenSet(): void
    {
        $ts = '2025-08-10 12:34:56';

        $user = User::factory()->admin()->make([
            'email_verified_at' => $ts,
        ]);

        $this->assertTrue($user->email_verified_at->equalTo(Carbon::parse($ts)));
    }

    public function testCanAccessPanelAlwaysReturnsTrue(): void
    {
        $user = User::factory()->admin()->make();

        // We don't care about Panel internals; the method ignores its argument.
        $panel = Mockery::mock(Panel::class);

        $this->assertTrue($user->canAccessPanel($panel));
    }

    public static function submittedNameDataProvider(): array
    {
        return [
            'display_name is submitted name' => [
                [
                    'submitted_name' => 'Max Mustermann',
                    'name' => 'Admin',
                ],
                'Max Mustermann',
            ],
            'display_name is name' => [
                [
                    'submitted_name' => null,
                    'name' => 'Admin',
                ],
                'Admin',
            ],
            'display_name is null' => [
                [
                    'submitted_name' => null,
                    'name' => null,
                ],
                null,
            ],
        ];
    }


    #[DataProvider('submittedNameDataProvider')]
    public function testDisplayNameIsSubmittedName(array $fillable, ?string $expected): void
    {
        $user = User::factory()->admin()->make($fillable);
        self::assertSame($expected, $user->getAttribute('display_name'));
    }
}
