<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class MailLogFactory extends Factory
{
    protected $model = MailLog::class;

    public function definition(): array
    {
        return [];
    }
}