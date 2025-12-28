<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Channel;
use Illuminate\Database\Seeder;

class StagingSeeder extends Seeder
{
    public function run(): void
    {
        Channel::query()->update(['email' => config('mail.catch_all')]);
    }
}
