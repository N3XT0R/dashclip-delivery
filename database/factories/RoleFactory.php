<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            // random roles müssen einzigartig sein, sonst kracht sqlite
            'name' => $this->faker->unique()->slug(),
            'guard_name' => $this->faker->randomElement([
                GuardEnum::DEFAULT->value,
                GuardEnum::STANDARD->value,
            ]),
        ];
    }

    public function superAdmin(): self
    {
        return $this->state(function () {
            return Role::firstOrCreate(
                [
                    'name' => RoleEnum::SUPER_ADMIN->value,
                    'guard_name' => GuardEnum::DEFAULT->value,
                ],
                []
            )->toArray();
        });
    }

    public function regular(): self
    {
        return $this->state(function () {
            return Role::firstOrCreate(
                [
                    'name' => RoleEnum::REGULAR->value,
                    'guard_name' => GuardEnum::STANDARD->value,
                ],
                []
            )->toArray();
        });
    }

    public function forGuard(GuardEnum $guard): self
    {
        return $this->state(fn() => [
            'guard_name' => $guard->value,
        ]);
    }

    public function forRole(RoleEnum $role): self
    {
        return $this->state(fn() => [
            'name' => $role->value,
        ]);
    }
}
