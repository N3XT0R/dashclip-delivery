<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Models\User;
use App\Repository\UserRepository;
use Tests\DatabaseTestCase;

class UserRepositoryTest extends DatabaseTestCase
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
}