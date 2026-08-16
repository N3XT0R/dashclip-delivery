<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\User;
use App\Repository\UserRepository;
use Spatie\Permission\Models\Role;
use Tests\DatabaseTestCase;

final class UserRepositoryTest extends DatabaseTestCase
{
    protected UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->app->make(UserRepository::class);
    }

    public function testFindsUserBySubmittedName(): void
    {
        $user = User::factory()->create([
            'submitted_name' => 'AlphaUser',
            'name' => 'OtherValue',
        ]);

        $found = $this->userRepository->getUserByDisplayName('AlphaUser');

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function testFindsUserByNameIfSubmittedNameDoesNotMatch(): void
    {
        $user = User::factory()->create([
            'submitted_name' => 'Unrelated',
            'name' => 'BetaUser',
        ]);

        $found = $this->userRepository->getUserByDisplayName('BetaUser');

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function testFindsFirstUserWhenBothFieldsMatchAcrossUsers(): void
    {
        $first = User::factory()->create([
            'submitted_name' => 'GammaUser',
            'name' => 'DifferentValue',
        ]);

        User::factory()->create([
            'submitted_name' => 'Other',
            'name' => 'GammaUser',
        ]);

        $found = $this->userRepository->getUserByDisplayName('GammaUser');

        $this->assertNotNull($found);
        $this->assertSame($first->id, $found->id);
    }

    public function testReturnsNullWhenNoUserMatches(): void
    {
        $found = $this->userRepository->getUserByDisplayName('DoesNotExist');

        $this->assertNull($found);
    }

    public function testReturnsFirstMatchingUserWhenMultipleMatch(): void
    {
        $first = User::factory()->create([
            'submitted_name' => 'DeltaUser',
        ]);

        User::factory()->create([
            'submitted_name' => 'DeltaUser',
        ]);

        $found = $this->userRepository->getUserByDisplayName('DeltaUser');

        $this->assertNotNull($found);
        $this->assertSame($first->id, $found->id);
    }

    public function testDoesNotReturnUserWhenNamesDoNotMatch(): void
    {
        User::factory()->create([
            'submitted_name' => 'Irrelevant',
            'name' => 'AlsoIrrelevant',
        ]);

        $found = $this->userRepository->getUserByDisplayName('TargetName');

        $this->assertNull($found);
    }

    public function testGetAllReturnsAll(): void
    {
        $all = $this->userRepository->getAllUsers();
        $count = $all->count();

        $this->assertDatabaseCount('users', $count);
    }

    public function testExcludesUserWhoNeverLoggedIn(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => null,
        ]);

        $ids = $this->userRepository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertNotContains($user->getKey(), $ids->all());
    }

    public function testExcludesUserWhoLoggedInRecently(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(3),
        ]);

        $ids = $this->userRepository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertNotContains($user->getKey(), $ids->all());
    }

    public function testIncludesInactiveUserWithNoPriorReminder(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(8),
            'last_login_reminder_sent_at' => null,
        ]);

        $ids = $this->userRepository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertContains($user->getKey(), $ids->all());
    }

    public function testExcludesUserRemindedRecently(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(10),
            'last_login_reminder_sent_at' => now()->subDays(2),
        ]);

        $ids = $this->userRepository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertNotContains($user->getKey(), $ids->all());
    }

    public function testIncludesUserRemindedLongAgo(): void
    {
        $user = User::factory()->standard(GuardEnum::STANDARD)->create([
            'last_login_at' => now()->subDays(20),
            'last_login_reminder_sent_at' => now()->subDays(9),
        ]);

        $ids = $this->userRepository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertContains($user->getKey(), $ids->all());
    }

    public function testExcludesUserWithoutStandardGuardRole(): void
    {
        $user = User::factory()->admin(GuardEnum::DEFAULT)->create([
            'last_login_at' => now()->subDays(10),
        ]);

        $ids = $this->userRepository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertNotContains($user->getKey(), $ids->all());
    }

    public function testExcludesUserWithSuperAdminRoleEvenIfTheyAlsoHaveStandardGuardRole(): void
    {
        $user = User::factory()
            ->standard(GuardEnum::STANDARD)
            ->create(['last_login_at' => now()->subDays(10)]);

        $superAdminRole = Role::firstOrCreate([
            'name' => RoleEnum::SUPER_ADMIN->value,
            'guard_name' => GuardEnum::DEFAULT->value,
        ]);
        $user->assignRole($superAdminRole);

        $ids = $this->userRepository->getUsersEligibleForInactivityReminder(7)->pluck('id');

        $this->assertNotContains($user->getKey(), $ids->all());
    }
}
