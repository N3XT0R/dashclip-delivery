<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Resources\UserResource;

use App\Enum\Users\RoleEnum;
use App\Events\User\UserCreated;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * Integration tests for Filament v4 CreateUser page.
 *
 * Verifies:
 *  - SuperAdmins can create users via the Filament interface
 *  - Password generation works when no password is provided
 *  - UserCreated event is dispatched with correct parameters
 *  - Regular users are forbidden from accessing this page
 */
final class CreateUserPageTest extends DatabaseTestCase
{
    private User $admin;
    private User $regular;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->regular = User::factory()->standard()->create();
    }

    public function testSuperAdminCanCreateUserWithoutPasswordAndEventIsDispatched(): void
    {
        Event::fake([UserCreated::class]);

        $this->actingAs($this->admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'John Doe',
                'email' => 'john@example.test',
                // no password field → triggers random password generation
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'john@example.test')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(RoleEnum::REGULAR->value));

        Event::assertDispatched(UserCreated::class, function (UserCreated $event) use ($user) {
            return $event->user->is($user)
                && $event->fromBackend === true
                && !empty($event->plainPassword);
        });
    }

    public function testRegularUserCannotAccessCreateUserPage(): void
    {
        $this->actingAs($this->regular);

        Livewire::test(CreateUser::class)
            ->assertForbidden();
    }
}
