<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Auth;

use App\Enum\Guard\GuardEnum;
use App\Exceptions\Auth\ImpersonationNotAllowedException;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use Tests\DatabaseTestCase;

final class ImpersonationServiceTest extends DatabaseTestCase
{
    private ImpersonationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ImpersonationService::class);
    }

    public function testStartSignsTheAdminInAsTheOtherUserAndRemembersWhoItWas(): void
    {
        $admin = User::factory()->admin()->create();
        $operator = User::factory()->standard()->create();
        $this->actingAs($admin, GuardEnum::DEFAULT->value);

        $this->service->start($admin, $operator);

        self::assertTrue($this->service->isActive());
        self::assertTrue(auth(GuardEnum::STANDARD->value)->user()->is($operator));
        self::assertTrue($this->service->impersonator()->is($admin));
    }

    public function testStopSignsTheOtherUserOutAgain(): void
    {
        $admin = User::factory()->admin()->create();
        $operator = User::factory()->standard()->create();
        $this->actingAs($admin, GuardEnum::DEFAULT->value);
        $this->service->start($admin, $operator);

        $this->service->stop();

        self::assertFalse($this->service->isActive());
        self::assertNull(auth(GuardEnum::STANDARD->value)->user());
        self::assertNull($this->service->impersonator());
    }

    public function testBothStartAndStopAreRecordedInTheActivityLog(): void
    {
        $admin = User::factory()->admin()->create();
        $operator = User::factory()->standard()->create();
        $this->actingAs($admin, GuardEnum::DEFAULT->value);

        $this->service->start($admin, $operator);
        $this->service->stop();

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'impersonation',
            'description' => 'impersonation started',
            'causer_id' => $admin->getKey(),
            'subject_id' => $operator->getKey(),
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'impersonation',
            'description' => 'impersonation stopped',
            'causer_id' => $admin->getKey(),
            'subject_id' => $operator->getKey(),
        ]);
    }

    public function testAnAdminCannotImpersonateThemselves(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, GuardEnum::DEFAULT->value);

        $this->expectException(ImpersonationNotAllowedException::class);

        $this->service->start($admin, $admin);
    }

    public function testAnotherAdminCannotBeImpersonated(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $this->actingAs($admin, GuardEnum::DEFAULT->value);

        $this->expectException(ImpersonationNotAllowedException::class);

        $this->service->start($admin, $other);
    }

    public function testAUserWithoutAccessToTheUserAreaCannotBeImpersonated(): void
    {
        $admin = User::factory()->admin()->create();
        // every account starts with the default role of the user area; this one had it taken away
        $outsider = User::factory()->create();
        $outsider->syncRoles([]);
        $this->actingAs($admin, GuardEnum::DEFAULT->value);

        $this->expectException(ImpersonationNotAllowedException::class);

        $this->service->start($admin, $outsider);
    }

    public function testARunningImpersonationCannotBeStackedOnAnother(): void
    {
        $admin = User::factory()->admin()->create();
        $first = User::factory()->standard()->create();
        $second = User::factory()->standard()->create();
        $this->actingAs($admin, GuardEnum::DEFAULT->value);
        $this->service->start($admin, $first);

        $this->expectException(ImpersonationNotAllowedException::class);

        $this->service->start($admin, $second);
    }

    public function testOnlyAnAdminMayImpersonate(): void
    {
        $operator = User::factory()->standard()->create();
        $target = User::factory()->standard()->create();
        $this->actingAs($operator, GuardEnum::STANDARD->value);

        $this->expectException(ImpersonationNotAllowedException::class);

        $this->service->start($operator, $target);
    }
}
