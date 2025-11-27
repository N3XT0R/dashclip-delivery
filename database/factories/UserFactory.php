<?php

namespace Database\Factories;

use App\Enum\Users\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Default state for Admin-User
     */
    public function admin(string $guard = 'web'): static
    {
        return $this->withRole(RoleEnum::SUPER_ADMIN, $guard);
    }

    /**
     * Default state for Standard-User
     */
    public function standard(string $guard = 'standard'): static
    {
        return $this->withRole(RoleEnum::REGULAR, $guard);
    }

    public function withRole(RoleEnum $roleName, ?string $guard = null): static
    {
        return $this->afterCreating(function (User $user) use ($roleName, $guard) {
            $data = ['name' => $roleName->value];
            if ($guard) {
                $data['guard_name'] = $guard;
            }
            $role = Role::firstOrCreate($data);

            $user->syncRoles([$role]);
        });
    }

    public function withOwnTeam(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->teams()->create([
                'name' => $user->name."'s Team",
                'slug' => Str::uuid(),
                'owner_id' => $user->getKey(),
            ]);
        });
    }
}
