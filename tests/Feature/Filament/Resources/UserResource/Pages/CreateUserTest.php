<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\UserResource\Pages;

use App\Enum\Users\RoleEnum;
use App\Events\User\UserCreated;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class CreateUserTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin, 'web');
    }

    public function testCreatesUserWithGeneratedPasswordAndDefaultRole(): void
    {
        Event::fake([UserCreated::class]);

        Livewire::test(CreateUser::class)
            ->assertStatus(200)
            ->fillForm([
                'name' => 'Generated User',
                'email' => 'generated@example.com',
                'password' => '',
                'roles' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'generated@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(RoleEnum::REGULAR->value));

        Event::assertDispatched(UserCreated::class, function (UserCreated $event) use ($user): bool {
            return $event->user->is($user)
                && $event->fromBackend === true
                && is_string($event->plainPassword)
                && $event->plainPassword !== '';
        });
    }

    public function testCreatesUserWithProvidedPassword(): void
    {
        Event::fake([UserCreated::class]);

        $password = 'Secure123!';

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Custom Password',
                'email' => 'custom@example.com',
                'password' => $password,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'custom@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check($password, $user->password));

        Event::assertDispatched(UserCreated::class, function (UserCreated $event) use ($user, $password): bool {
            return $event->user->is($user)
                && $event->plainPassword === $password
                && $event->fromBackend === true;
        });
    }
}
