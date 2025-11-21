<?php

namespace Database\Seeders;

use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = app(UserRepository::class)->getAllUsers();
        foreach ($users as $user) {
            app(TeamRepository::class)->createOwnTeamForUser($user);
        }
    }
}
