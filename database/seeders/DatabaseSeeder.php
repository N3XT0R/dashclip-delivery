<?php

namespace Database\Seeders;

use App\Enum\Users\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assignRole(RoleEnum::SUPER_ADMIN->value);

        $this->call([
            ShieldSeeder::class,
        ]);
    }
}
