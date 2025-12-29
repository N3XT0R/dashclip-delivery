<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Traits;

use App\Application\Channel\GetCurrentChannel;
use App\Enum\Users\RoleEnum;
use App\Filament\Traits\ChannelOwnerContextTrait;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Tests\DatabaseTestCase;

final class ChannelOwnerContextTraitTest extends DatabaseTestCase
{
    private function createTraitInstance(): object
    {
        return new class () {
            use ChannelOwnerContextTrait {
                getCurrentChannel as public getCurrentChannelPublic;
                getCurrentChannelOnlyIfHaveAccess as public getCurrentChannelOnlyIfHaveAccessPublic;
            }
        };
    }

    public function testGetCurrentChannelReturnsValueFromHandler(): void
    {
        $channel = Channel::factory()->create();
        app()->bind(GetCurrentChannel::class, fn() => new class ($channel) {
            public function __construct(private ?Channel $channel)
            {
            }

            public function handle(): ?Channel
            {
                return $this->channel;
            }
        });

        $this->assertTrue(
            $channel->is($this->createTraitInstance()->getCurrentChannelPublic())
        );
    }

    public function testGetCurrentChannelOnlyIfHaveAccessReturnsNullWhenNoChannel(): void
    {
        app()->bind(GetCurrentChannel::class, fn() => new class () {
            public function handle(): ?Channel
            {
                return null;
            }
        });

        $this->assertNull(
            $this->createTraitInstance()->getCurrentChannelOnlyIfHaveAccessPublic()
        );
    }

    public function testGetCurrentChannelOnlyIfHaveAccessReturnsNullWhenUserLacksAccess(): void
    {
        $channel = Channel::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        Gate::define('page.channels.access_for_channel', fn(User $authUser, Channel $gateChannel) => false);

        app()->bind(GetCurrentChannel::class, fn() => new class ($channel) {
            public function __construct(private Channel $channel)
            {
            }

            public function handle(): Channel
            {
                return $this->channel;
            }
        });

        $this->assertNull(
            $this->createTraitInstance()->getCurrentChannelOnlyIfHaveAccessPublic()
        );
    }

    public function testGetCurrentChannelOnlyIfHaveAccessReturnsChannelForAuthorizedUser(): void
    {
        $channel = Channel::factory()->create();
        $user = User::factory()
            ->create();
        $this->actingAs($user);

        Gate::define('page.channels.access_for_channel', fn(User $authUser, Channel $gateChannel) =>
            $authUser->is($user) && $gateChannel->is($channel)
        );

        app()->bind(GetCurrentChannel::class, fn() => new class ($channel) {
            public function __construct(private Channel $channel)
            {
            }

            public function handle(): Channel
            {
                return $this->channel;
            }
        });

        $this->assertTrue(
            $channel->is($this->createTraitInstance()->getCurrentChannelOnlyIfHaveAccessPublic())
        );
    }

    public function testUserHasAccessToChannelRejectsNonChannelModels(): void
    {
        $traitClass = get_class($this->createTraitInstance());

        $this->assertFalse($traitClass::userHasAccessToChannel(new class () extends Model {
        }));
    }

    public function testUserHasAccessToChannelRequiresAuthenticatedUser(): void
    {
        $channel = Channel::factory()->create();
        $traitClass = get_class($this->createTraitInstance());

        $this->assertFalse($traitClass::userHasAccessToChannel($channel));
    }

    public function testUserHasAccessToChannelRespectsChannelAuthorization(): void
    {
        $channel = Channel::factory()->create();
        $userWithoutAccess = User::factory()->create();
        $authorizedUser = User::factory()->create();
        $traitClass = get_class($this->createTraitInstance());

        Gate::define('page.channels.access_for_channel', function (User $authUser, Channel $gateChannel) use ($authorizedUser, $channel) {
            return $authUser->is($authorizedUser) && $gateChannel->is($channel);
        });

        $this->assertFalse($traitClass::userHasAccessToChannel($channel, $userWithoutAccess));
        $this->assertTrue($traitClass::userHasAccessToChannel($channel, $authorizedUser));
    }
}
