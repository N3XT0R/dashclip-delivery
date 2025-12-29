<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Traits;

use App\Filament\Traits\HasChannelAuthorizationTrait;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\DatabaseTestCase;

final class HasChannelAuthorizationTraitTest extends DatabaseTestCase
{
    private function createTraitInstance(): object
    {
        return new class () {
            use HasChannelAuthorizationTrait;
        };
    }

    public function testCrudPermissionsDenyUnauthorizedUsers(): void
    {
        $channel = Channel::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        Gate::define('page.channels.access_for_channel', fn(User $authUser, Channel $gateChannel) => false);

        $traitClass = get_class($this->createTraitInstance());

        foreach (['canEdit', 'canView', 'canDelete', 'canForceDelete', 'canRestore'] as $method) {
            $this->assertFalse($traitClass::$method($channel));
        }
    }

    public function testCrudPermissionsAllowAuthorizedUsers(): void
    {
        $channel = Channel::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        Gate::define('page.channels.access_for_channel', fn(User $authUser, Channel $gateChannel) =>
            $authUser->is($user) && $gateChannel->is($channel)
        );

        $traitClass = get_class($this->createTraitInstance());

        foreach (['canEdit', 'canView', 'canDelete', 'canForceDelete', 'canRestore'] as $method) {
            $this->assertTrue($traitClass::$method($channel));
        }
    }
}
