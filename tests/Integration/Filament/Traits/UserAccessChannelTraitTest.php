<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Traits;

use App\Filament\Traits\UserAccessChannelTrait;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\DatabaseTestCase;

final class UserAccessChannelTraitTest extends DatabaseTestCase
{
    private function createTraitInstance(): object
    {
        return new class () {
            use UserAccessChannelTrait {
                userCanAccessChannelPage as public userCanAccessChannelPagePublic;
            }
        };
    }

    public function testInstanceMethodRequiresAuthenticatedUser(): void
    {
        $this->assertFalse($this->createTraitInstance()->userCanAccessChannelPagePublic());
    }

    public function testInstanceMethodDeniesUserWithoutRoleOrChannelAccess(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Gate::define('page.channels.access', fn() => false);

        $this->assertFalse($this->createTraitInstance()->userCanAccessChannelPagePublic());
    }

    public function testInstanceMethodAllowsUserWithChannelAccess(): void
    {
        $channel = Channel::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        Gate::define('page.channels.access', fn(User $authUser) => $authUser->is($user));

        $this->assertTrue($this->createTraitInstance()->userCanAccessChannelPagePublic());
    }

    public function testStaticMethodReturnsFalseWithoutUser(): void
    {
        $traitClass = get_class($this->createTraitInstance());

        $this->assertFalse($traitClass::userCanAccessChannelPageStatic());
    }

    public function testStaticMethodUsesProvidedUser(): void
    {
        $channel = Channel::factory()->create();
        $user = User::factory()->create();

        $traitClass = get_class($this->createTraitInstance());

        Gate::define('page.channels.access', fn(User $authUser) => $authUser->is($user));

        $this->assertTrue($traitClass::userCanAccessChannelPageStatic($user));
    }
}
